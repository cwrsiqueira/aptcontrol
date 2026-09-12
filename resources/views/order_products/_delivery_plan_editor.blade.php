@php
    $initialDeliveryPlan = old('delivery_plan', $deliveryPlan ?? []);
@endphp

<div class="card bg-light border mt-3 mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h6 class="mb-1">Fracionamento e carga por entrega @includeIf('partials.change_marker')</h6>
                <small class="text-muted">As entregas têm quantidades iguais, datas diferentes e composição de carga própria.</small>
            </div>
            <div class="form-group mb-0 mt-2 mt-sm-0 delivery-count-field">
                <label for="delivery_count" class="mb-1">Forma de entrega</label>
                <select id="delivery_count" class="form-control form-control-sm"></select>
            </div>
        </div>

        <div id="delivery_plan_empty" class="alert alert-secondary mb-0">
            Informe a quantidade do produto para montar o planejamento.
        </div>
        <div id="delivery_plan_rows" class="d-none"></div>
    </div>
</div>

@push('css')
    <style>
        .delivery-count-field { min-width: 240px; }
        .delivery-availability { white-space: normal; line-height: 1.35; }
        .delivery-load-card { border-left: 3px solid #007bff; }
        .delivery-load-summary .form-control { min-width: 105px; }
    </style>
@endpush

@push('js')
    <script src="{{ asset('js/pallet-recommendation.js') }}"></script>
    <script>
        (function() {
            const quant = document.querySelector('#quant');
            const deliveryDate = document.querySelector('#delivery_date');
            const deliveryCount = document.querySelector('#delivery_count');
            const rowsContainer = document.querySelector('#delivery_plan_rows');
            const emptyMessage = document.querySelector('#delivery_plan_empty');
            const initialPlan = @json($initialDeliveryPlan);
            const isCif = @json(strtolower($order->withdraw) === 'entregar');
            const availabilityUrl = @json(route('order_products.truck_availability'));
            const maxDeliveries = 100;

            function parseQuantity(value) {
                return Number(String(value || '').replace(/\D/g, '')) || 0;
            }

            function addDays(date, days) {
                const parts = String(date || '').split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return date;
                const result = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
                result.setUTCDate(result.getUTCDate() + days);
                return result.toISOString().slice(0, 10);
            }

            function divisors(total) {
                const values = [];
                for (let count = 1; count <= Math.sqrt(total); count++) {
                    if (total % count !== 0) continue;
                    if (count <= maxDeliveries) values.push(count);
                    const pair = total / count;
                    if (pair !== count && pair <= maxDeliveries) values.push(pair);
                }
                return values.sort((a, b) => a - b);
            }

            function currentPlan() {
                return Array.from(rowsContainer.querySelectorAll('.delivery-load-card')).map(card => ({
                    id: Number(card.querySelector('.delivery-plan-id')?.value) || null,
                    date: card.querySelector('.delivery-plan-date')?.value || '',
                    quantity: Number(card.dataset.quantity) || 0,
                    palete_tipo: Array.from(card.querySelectorAll('.delivery-pallet-type')).map(field => field.value),
                    palete_quant: Array.from(card.querySelectorAll('.delivery-pallet-count')).map(field => field.value)
                }));
            }

            function hasPalletComposition(plan) {
                return (plan.palete_tipo || []).some((type, index) =>
                    Number(type) > 0 && Number(plan.palete_quant?.[index]) > 0
                );
            }

            function recommendedPlan(quantity) {
                const items = window.PalletRecommendation.recommend(quantity);
                return {
                    palete_tipo: items.map(item => item.capacity),
                    palete_quant: items.map(item => item.count),
                    recommendationStatus: items.length ? 'recommended' : 'unavailable'
                };
            }

            function preparePalletPlan(saved, quantity) {
                const savedQuantity = Number(saved.quantity) || 0;
                if (hasPalletComposition(saved) && (!savedQuantity || savedQuantity === quantity)) {
                    return { ...saved, recommendationStatus: 'saved' };
                }

                return { ...saved, ...recommendedPlan(quantity) };
            }

            function recommendationBadge(status) {
                if (status === 'recommended') return '<span class="badge badge-info delivery-pallet-status">Sugestão automática</span>';
                if (status === 'unavailable') return '<span class="badge badge-warning delivery-pallet-status">Sem combinação exata</span>';
                return '<span class="badge badge-light delivery-pallet-status">Composição salva</span>';
            }

            function palletRows(index, saved) {
                let html = '';
                for (let slot = 0; slot < 3; slot++) {
                    const type = Number(saved.palete_tipo?.[slot]) || '';
                    const count = Number(saved.palete_quant?.[slot]) || '';
                    html += `<tr>
                        <td><input type="number" min="1" name="delivery_plan[${index}][palete_tipo][${slot}]" class="form-control form-control-sm delivery-pallet-type" value="${type}"></td>
                        <td><input type="number" min="1" name="delivery_plan[${index}][palete_quant][${slot}]" class="form-control form-control-sm delivery-pallet-count" value="${count}"></td>
                        <td><input type="text" class="form-control form-control-sm delivery-pallet-row-total" value="0" readonly></td>
                    </tr>`;
                }
                return html;
            }

            function render(count, savedPlan = []) {
                const total = parseQuantity(quant.value);
                const perDelivery = total / count;
                if (!Number.isInteger(perDelivery) || perDelivery <= 0) {
                    rebuild(1, []);
                    return;
                }

                let previousDate = '';
                let html = '';
                for (let index = 0; index < count; index++) {
                    const saved = preparePalletPlan(savedPlan[index] || {}, perDelivery);
                    const defaultDate = addDays(deliveryDate.value, index);
                    const minimumDate = index === 0 ? deliveryDate.value : addDays(previousDate, 1);
                    let date = /^\d{4}-\d{2}-\d{2}$/.test(saved.date || '') ? saved.date : defaultDate;
                    if (date < minimumDate) date = minimumDate;
                    previousDate = date;

                    html += `<div class="card delivery-load-card mb-3" data-quantity="${perDelivery}">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                            <strong>Entrega ${index + 1} @includeIf('partials.change_marker')</strong>
                            <span class="badge badge-light delivery-availability">${isCif ? 'Consultando caminhões...' : 'Retirada pelo cliente (FOB)'}</span>
                        </div>
                        <div class="card-body py-3">
                            ${saved.id ? `<input type="hidden" class="delivery-plan-id" name="delivery_plan[${index}][id]" value="${Number(saved.id)}">` : ''}
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>Quantidade</label>
                                    <input type="text" class="form-control" value="${perDelivery.toLocaleString('pt-BR')}" readonly>
                                    <input type="hidden" name="delivery_plan[${index}][quantity]" value="${perDelivery}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>Data prevista</label>
                                    <input type="date" name="delivery_plan[${index}][date]" class="form-control delivery-plan-date" value="${date}" min="${minimumDate}" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                        <label class="mb-0">Composição da carga @includeIf('partials.change_marker')</label>
                                        <div>
                                            ${recommendationBadge(saved.recommendationStatus)}
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-1">
                                            <thead><tr><th>Palete (capacidade)</th><th>Palete (quantidade)</th><th>Palete (total)</th></tr></thead>
                                            <tbody>${palletRows(index, saved)}</tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row delivery-load-summary">
                                <div class="col-md-3 ml-md-auto"><label>Carga (total)</label><input type="text" class="form-control delivery-load-total" value="0" readonly></div>
                                <div class="col-md-3"><label>Paletes (total)</label><input type="text" class="form-control delivery-pallet-total" value="0" readonly></div>
                                <div class="col-md-3"><label>Carga (diferença)</label><input type="text" class="form-control delivery-load-difference" value="0" readonly></div>
                            </div>
                        </div>
                    </div>`;
                }

                rowsContainer.innerHTML = html;
                rowsContainer.classList.remove('d-none');
                emptyMessage.classList.add('d-none');
                rowsContainer.querySelectorAll('.delivery-plan-date').forEach(field => field.addEventListener('change', recalculateDates));
                rowsContainer.querySelectorAll('.delivery-pallet-type, .delivery-pallet-count').forEach(field => field.addEventListener('input', markManualAdjustment));
                rowsContainer.querySelectorAll('.delivery-load-card').forEach(calculateCard);
                refreshAvailability();
            }

            function recalculateDates(event) {
                const fields = Array.from(rowsContainer.querySelectorAll('.delivery-plan-date'));
                const changedIndex = fields.indexOf(event.currentTarget);
                if (changedIndex < 0) return;

                const minimumDate = changedIndex === 0
                    ? deliveryDate.value
                    : addDays(fields[changedIndex - 1].value, 1);

                if (!fields[changedIndex].value || fields[changedIndex].value < minimumDate) {
                    fields[changedIndex].value = minimumDate;
                }

                for (let index = changedIndex + 1; index < fields.length; index++) {
                    fields[index].value = addDays(fields[index - 1].value, 1);
                }

                fields.forEach((field, index) => {
                    field.min = index === 0 ? deliveryDate.value : addDays(fields[index - 1].value, 1);
                });

                refreshAvailability();
            }

            function setRecommendationStatus(card, status) {
                const badge = card.querySelector('.delivery-pallet-status');
                badge.className = `badge delivery-pallet-status badge-${status === 'recommended' ? 'info' : (status === 'unavailable' ? 'warning' : 'light')}`;
                badge.textContent = status === 'recommended'
                    ? 'Sugestão automática'
                    : (status === 'unavailable' ? 'Sem combinação exata' : 'Ajustado manualmente');
            }

            function markManualAdjustment(event) {
                const card = event.currentTarget.closest('.delivery-load-card');
                setRecommendationStatus(card, 'manual');
                calculateCard(card);
            }

            function calculateCard(eventOrCard) {
                const card = eventOrCard.currentTarget ? eventOrCard.currentTarget.closest('.delivery-load-card') : eventOrCard;
                const types = card.querySelectorAll('.delivery-pallet-type');
                const counts = card.querySelectorAll('.delivery-pallet-count');
                const rowTotals = card.querySelectorAll('.delivery-pallet-row-total');
                let loadTotal = 0;
                let palletTotal = 0;
                types.forEach((field, index) => {
                    const type = Number(field.value) || 0;
                    const count = Number(counts[index].value) || 0;
                    const rowTotal = type * count;
                    rowTotals[index].value = rowTotal.toLocaleString('pt-BR');
                    loadTotal += rowTotal;
                    palletTotal += count;
                });
                const quantity = Number(card.dataset.quantity) || 0;
                card.querySelector('.delivery-load-total').value = loadTotal.toLocaleString('pt-BR');
                card.querySelector('.delivery-pallet-total').value = palletTotal.toLocaleString('pt-BR');
                card.querySelector('.delivery-load-difference').value = (quantity - loadTotal).toLocaleString('pt-BR');
            }

            function refreshAvailability() {
                if (!isCif) return;
                rowsContainer.querySelectorAll('.delivery-plan-date').forEach(field => {
                    const badge = field.closest('.delivery-load-card').querySelector('.delivery-availability');
                    $.getJSON(availabilityUrl, { date: field.value })
                        .done(resp => {
                            badge.className = `badge delivery-availability badge-${resp.available > 0 ? 'success' : 'danger'}`;
                            badge.textContent = `${resp.available} de ${resp.total} caminhões disponíveis em ${field.value.split('-').reverse().join('/')}`;
                        })
                        .fail(() => {
                            badge.className = 'badge badge-warning delivery-availability';
                            badge.textContent = 'Disponibilidade não consultada';
                        });
                });
            }

            function rebuild(preferredCount, savedPlan = currentPlan()) {
                const total = parseQuantity(quant.value);
                const options = divisors(total);
                deliveryCount.innerHTML = '';
                if (!options.length) {
                    rowsContainer.classList.add('d-none');
                    emptyMessage.classList.remove('d-none');
                    return;
                }
                options.forEach(count => {
                    const option = document.createElement('option');
                    option.value = count;
                    option.textContent = `${count} ${count === 1 ? 'entrega' : 'entregas'} de ${(total / count).toLocaleString('pt-BR')}`;
                    deliveryCount.appendChild(option);
                });
                const selected = options.includes(Number(preferredCount)) ? Number(preferredCount) : 1;
                deliveryCount.value = selected;
                render(selected, savedPlan.length === selected ? savedPlan : []);
            }

            function setMinimumDate(date) {
                deliveryDate.value = date;
                const plan = currentPlan();
                const used = new Set();
                plan.forEach((item, index) => {
                    if (!item.date || item.date < date || used.has(item.date)) item.date = addDays(date, index);
                    while (used.has(item.date)) item.date = addDays(item.date, 1);
                    used.add(item.date);
                });
                render(Number(deliveryCount.value) || 1, plan);
            }

            deliveryCount.addEventListener('change', function() {
                render(Number(this.value));
            });
            quant.addEventListener('blur', function() {
                rebuild(Number(deliveryCount.value) || 1);
            });

            window.deliveryPlanEditor = { rebuild, setMinimumDate };
            rebuild(initialPlan.length || 1, initialPlan);
        })();
    </script>
@endpush
