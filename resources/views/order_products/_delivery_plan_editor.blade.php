{{-- Mantém a composição detalhada na edição. --}}
@php
    $initialDeliveryPlan = old('delivery_plan', $deliveryPlan ?? []);
@endphp

<div class="card bg-light border mt-3 mb-3" data-delivery-plan-editor
    data-initial-plan="{{ base64_encode(json_encode($initialDeliveryPlan)) }}"
    data-is-cif="{{ strtolower($order->withdraw) === 'entregar' ? '1' : '0' }}"
    data-schedule-url="{{ route('order_products.truck_availability') }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div>
                <h6 class="mb-1">Fracionamento e carga por entrega @includeIf('partials.change_marker')</h6>
                <small class="text-muted">As entregas têm quantidades iguais e as opções disponíveis não deixam espaço livre nos paletes.</small>
            </div>
            <div class="form-group mb-0 mt-2 mt-sm-0 delivery-count-field">
                <label for="delivery_count" class="mb-1">Forma de entrega</label>
                <select id="delivery_count" class="form-control form-control-sm"></select>
            </div>
        </div>

        <div id="delivery_plan_empty" class="alert alert-secondary mb-0">
            Informe a quantidade do produto para montar o planejamento.
        </div>
        <div id="delivery_plan_exact_warning" class="alert alert-warning d-none mb-3">
            A configuração atual deixa espaço livre. Ajuste a composição para liberar as formas de entrega compatíveis.
        </div>
        <div id="delivery_plan_rows" class="d-none"></div>
    </div>
</div>

@push('css')
    <style>
        .delivery-count-field { min-width: 240px; }
        .delivery-schedule { white-space: normal; line-height: 1.35; }
        .delivery-load-card { border-left: 3px solid #007bff; }
        .delivery-load-summary .form-control { min-width: 105px; }
        .delivery-date-calendar { position: relative; }
        .delivery-date-calendar .delivery-plan-date-picker {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
@endpush

@push('js')
    <script>
        (function() {
            const editor = document.querySelector('[data-delivery-plan-editor]');
            const quant = document.querySelector('#quant');
            const deliveryDate = document.querySelector('#delivery_date');
            const deliveryCount = document.querySelector('#delivery_count');
            const rowsContainer = document.querySelector('#delivery_plan_rows');
            const emptyMessage = document.querySelector('#delivery_plan_empty');
            const exactWarning = document.querySelector('#delivery_plan_exact_warning');
            const initialPlan = JSON.parse(atob(editor.dataset.initialPlan || 'W10='));
            const isCif = editor.dataset.isCif === '1';
            const scheduleUrl = editor.dataset.scheduleUrl;
            const maxDeliveries = 100;
            let knownPalletCapacities = palletCapacities(initialPlan);

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

            function formatDate(date) {
                return /^\d{4}-\d{2}-\d{2}$/.test(date || '')
                    ? date.split('-').reverse().join('/')
                    : '';
            }

            function maskDate(value) {
                const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
                if (digits.length <= 2) return digits;
                if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
                return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }

            function parseDate(value) {
                const match = String(value || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                if (!match) return '';

                const day = Number(match[1]);
                const month = Number(match[2]);
                const year = Number(match[3]);
                const date = new Date(Date.UTC(year, month - 1, day));
                if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) {
                    return '';
                }

                return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            }

            function palletCapacities(plan) {
                return Array.from(new Set((plan || []).flatMap(item => item.palete_tipo || [])
                    .map(Number)
                    .filter(value => Number.isInteger(value) && value > 0)));
            }

            function greatestCommonDivisor(first, second) {
                let a = Math.abs(first);
                let b = Math.abs(second);
                while (b) [a, b] = [b, a % b];
                return a;
            }

            // Confirma se as capacidades conhecidas conseguem formar uma carga exata.
            function canComposeExactly(total, capacities) {
                if (!Number.isInteger(total) || total <= 0 || !capacities.length) return false;
                if (capacities.length === 1) return total % capacities[0] === 0;

                const divisor = capacities.reduce(greatestCommonDivisor);
                if (total % divisor !== 0) return false;

                const normalizedTotal = total / divisor;
                const normalizedCapacities = capacities.map(value => value / divisor);
                const base = Math.min(...normalizedCapacities);
                const distances = Array(base).fill(Infinity);
                distances[0] = 0;

                for (let pass = 0; pass < base; pass++) {
                    let changed = false;
                    for (let remainder = 0; remainder < base; remainder++) {
                        if (!Number.isFinite(distances[remainder])) continue;
                        normalizedCapacities.forEach(capacity => {
                            const next = (remainder + capacity) % base;
                            const distance = distances[remainder] + capacity;
                            if (distance < distances[next]) {
                                distances[next] = distance;
                                changed = true;
                            }
                        });
                    }
                    if (!changed) break;
                }

                return distances[normalizedTotal % base] <= normalizedTotal;
            }

            function divisors(total) {
                const values = [];
                if (!knownPalletCapacities.length) return values;

                for (let count = 1; count <= Math.sqrt(total); count++) {
                    if (total % count !== 0) continue;
                    if (count <= maxDeliveries && canComposeExactly(total / count, knownPalletCapacities)) values.push(count);
                    const pair = total / count;
                    if (pair !== count && pair <= maxDeliveries && canComposeExactly(total / pair, knownPalletCapacities)) values.push(pair);
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

            // Mantém somente a composição salva ou informada pelo usuário.
            function preparePalletPlan(saved) {
                return {
                    ...saved,
                    compositionStatus: hasPalletComposition(saved) ? 'saved' : 'empty'
                };
            }

            function compositionBadge(status) {
                if (status === 'empty') return '<span class="badge badge-warning delivery-pallet-status">Informe a composição</span>';
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
                    const saved = preparePalletPlan(savedPlan[index] || {});
                    const defaultDate = addDays(deliveryDate.value, index);
                    const minimumDate = index === 0 ? deliveryDate.value : addDays(previousDate, 1);
                    let date = /^\d{4}-\d{2}-\d{2}$/.test(saved.date || '') ? saved.date : defaultDate;
                    if (date < minimumDate) date = minimumDate;
                    previousDate = date;

                    html += `<div class="card delivery-load-card mb-3" data-quantity="${perDelivery}">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                            <strong>Entrega ${index + 1} @includeIf('partials.change_marker')</strong>
                            <span class="badge badge-light delivery-schedule">${isCif ? 'Consultando entregas...' : 'Retirada pelo cliente (FOB)'}</span>
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
                                    <div class="input-group">
                                        <input type="text" class="form-control delivery-plan-date-text" value="${formatDate(date)}"
                                            placeholder="dd/mm/aaaa" inputmode="numeric" maxlength="10" autocomplete="off" required>
                                        <div class="input-group-append delivery-date-calendar">
                                            <span class="input-group-text"><i class="fa fa-calendar-alt"></i></span>
                                            <input type="date" class="delivery-plan-date-picker" value="${date}" min="${minimumDate}"
                                                aria-label="Selecionar data no calendário">
                                        </div>
                                    </div>
                                    <input type="hidden" name="delivery_plan[${index}][date]" class="delivery-plan-date"
                                        value="${date}" data-minimum-date="${minimumDate}">
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                        <label class="mb-0">Composição da carga @includeIf('partials.change_marker')</label>
                                        <div>
                                            ${compositionBadge(saved.compositionStatus)}
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
                bindDateControls();
                rowsContainer.querySelectorAll('.delivery-pallet-type, .delivery-pallet-count').forEach(field => field.addEventListener('input', markManualAdjustment));
                rowsContainer.querySelectorAll('.delivery-load-card').forEach(calculateCard);
                refreshSchedule();
            }

            // Aceita data digitada e mantém o calendário sincronizado.
            function bindDateControls() {
                rowsContainer.querySelectorAll('.delivery-plan-date-text').forEach(field => {
                    field.addEventListener('focus', function() {
                        this.select();
                    });
                    field.addEventListener('mouseup', function(event) {
                        event.preventDefault();
                        this.select();
                    });
                    field.addEventListener('input', function() {
                        this.value = maskDate(this.value);
                        const date = parseDate(this.value);
                        this.setCustomValidity(date ? '' : 'Informe uma data válida.');
                        if (!date) return;
                        const hidden = this.closest('.form-group').querySelector('.delivery-plan-date');
                        hidden.value = date;
                        recalculateDates({ currentTarget: hidden });
                    });
                });

                rowsContainer.querySelectorAll('.delivery-plan-date-picker').forEach(field => {
                    field.addEventListener('change', function() {
                        const hidden = this.closest('.form-group').querySelector('.delivery-plan-date');
                        hidden.value = this.value;
                        recalculateDates({ currentTarget: hidden });
                    });
                });
            }

            function syncDateControls() {
                rowsContainer.querySelectorAll('.delivery-load-card').forEach(card => {
                    const hidden = card.querySelector('.delivery-plan-date');
                    const text = card.querySelector('.delivery-plan-date-text');
                    const picker = card.querySelector('.delivery-plan-date-picker');
                    text.value = formatDate(hidden.value);
                    text.setCustomValidity('');
                    picker.value = hidden.value;
                    picker.min = hidden.dataset.minimumDate || '';
                });
            }

            // Reorganiza as próximas datas em sequência.
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
                    field.dataset.minimumDate = index === 0 ? deliveryDate.value : addDays(fields[index - 1].value, 1);
                });

                syncDateControls();
                refreshSchedule();
            }

            function setCompositionStatus(card) {
                const badge = card.querySelector('.delivery-pallet-status');
                badge.className = 'badge badge-info delivery-pallet-status';
                badge.textContent = 'Informado pelo usuário';
            }

            function markManualAdjustment(event) {
                const card = event.currentTarget.closest('.delivery-load-card');
                setCompositionStatus(card);
                calculateCard(card);
                knownPalletCapacities = palletCapacities(currentPlan());
                refreshDeliveryOptions();
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

            // Informa as entregas já previstas para cada data.
            function refreshSchedule() {
                if (!isCif) return;
                rowsContainer.querySelectorAll('.delivery-plan-date').forEach(field => {
                    const badge = field.closest('.delivery-load-card').querySelector('.delivery-schedule');
                    $.getJSON(scheduleUrl, { date: field.value })
                        .done(resp => {
                            const planned = Number(resp.planned) || 0;
                            const date = field.value.split('-').reverse().join('/');
                            badge.className = `badge delivery-schedule badge-${planned > 0 ? 'info' : 'light'}`;
                            badge.textContent = planned > 0
                                ? `${planned} ${planned === 1 ? 'entrega prevista' : 'entregas previstas'} em ${date}`
                                : `Nenhuma entrega prevista em ${date}`;
                        })
                        .fail(() => {
                            badge.className = 'badge badge-warning delivery-schedule';
                            badge.textContent = 'Entregas previstas não consultadas';
                        });
                });
            }

            function rebuild(preferredCount, savedPlan = currentPlan()) {
                const total = parseQuantity(quant.value);
                const options = divisors(total);
                deliveryCount.innerHTML = '';

                if (!total) {
                    rowsContainer.classList.add('d-none');
                    emptyMessage.classList.remove('d-none');
                    exactWarning.classList.add('d-none');
                    return;
                }

                options.forEach(count => {
                    const option = document.createElement('option');
                    option.value = count;
                    option.textContent = `${count} ${count === 1 ? 'entrega' : 'entregas'} de ${(total / count).toLocaleString('pt-BR')}`;
                    deliveryCount.appendChild(option);
                });

                const preferred = Number(preferredCount);
                if (!options.includes(preferred) && savedPlan.length) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = options.length ? 'Selecione uma forma sem sobra' : 'Nenhuma forma compatível';
                    option.selected = true;
                    option.disabled = true;
                    deliveryCount.prepend(option);
                    exactWarning.classList.remove('d-none');
                    render(savedPlan.length, savedPlan);
                    deliveryCount.value = '';
                    return;
                }

                if (!options.length) {
                    rowsContainer.classList.add('d-none');
                    emptyMessage.textContent = 'Informe uma composição de paletes que complete a quantidade da entrega.';
                    emptyMessage.classList.remove('d-none');
                    exactWarning.classList.remove('d-none');
                    return;
                }

                exactWarning.classList.add('d-none');
                const selected = options.includes(preferred) ? preferred : options[0];
                deliveryCount.value = selected;
                render(selected, savedPlan.length === selected ? savedPlan : []);
            }

            function refreshDeliveryOptions() {
                const total = parseQuantity(quant.value);
                const currentCount = currentPlan().length;
                const options = divisors(total);
                deliveryCount.innerHTML = '';

                options.forEach(count => {
                    const option = document.createElement('option');
                    option.value = count;
                    option.textContent = `${count} ${count === 1 ? 'entrega' : 'entregas'} de ${(total / count).toLocaleString('pt-BR')}`;
                    deliveryCount.appendChild(option);
                });

                if (options.includes(currentCount)) {
                    deliveryCount.value = currentCount;
                    exactWarning.classList.add('d-none');
                    return;
                }

                const option = document.createElement('option');
                option.value = '';
                option.textContent = options.length ? 'Selecione uma forma sem sobra' : 'Nenhuma forma compatível';
                option.selected = true;
                option.disabled = true;
                deliveryCount.prepend(option);
                exactWarning.classList.remove('d-none');
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
                render(Number(deliveryCount.value) || plan.length || 1, plan);
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
