{{-- Calcula o fracionamento com uma capacidade de palete. --}}
@php
    $initialDeliveryPlan = old('delivery_plan', []);
@endphp

<div class="card bg-light border mt-2 mb-3" data-delivery-plan-editor
    data-initial-plan="{{ base64_encode(json_encode($initialDeliveryPlan)) }}"
    data-is-cif="{{ strtolower($order->withdraw) === 'entregar' ? '1' : '0' }}"
    data-schedule-url="{{ route('order_products.truck_availability') }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div class="mr-3">
                <h6 class="mb-1">Fracionamento e carga por entrega @includeIf('partials.change_marker')</h6>
                <small class="text-muted">Escolha a forma de entrega. As quantidades e os paletes serão calculados automaticamente.</small>
            </div>
            <div class="form-group mb-0 mt-2 mt-sm-0 delivery-count-field">
                <label for="delivery_count" class="mb-1">Forma de entrega</label>
                <select id="delivery_count" class="form-control form-control-sm"></select>
            </div>
        </div>

        <div id="delivery_plan_empty" class="alert alert-secondary mb-0">
            Informe a quantidade e a capacidade do palete para montar o planejamento.
        </div>
        <div id="delivery_plan_rows" class="d-none"></div>
    </div>
</div>

@push('css')
    <style>
        .delivery-count-field { min-width: 240px; }
        .delivery-schedule { white-space: normal; line-height: 1.35; }
        .delivery-load-card { border-left: 3px solid #007bff; }
        .delivery-calculated-value { font-weight: 600; background: #fff; }
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
            const palletCapacity = document.querySelector('#pallet_capacity');
            const deliveryDate = document.querySelector('#delivery_date');
            const deliveryCount = document.querySelector('#delivery_count');
            const rowsContainer = document.querySelector('#delivery_plan_rows');
            const emptyMessage = document.querySelector('#delivery_plan_empty');
            const initialPlan = JSON.parse(atob(editor.dataset.initialPlan || 'W10='));
            const isCif = editor.dataset.isCif === '1';
            const scheduleUrl = editor.dataset.scheduleUrl;
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

            // Lista apenas divisões com quantidades inteiras.
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
                    date: card.querySelector('.delivery-plan-date')?.value || ''
                }));
            }

            // Monta as entregas e calcula os paletes necessários.
            function render(count, savedPlan = []) {
                const total = parseQuantity(quant.value);
                const capacity = parseQuantity(palletCapacity.value);
                const perDelivery = total / count;

                if (!Number.isInteger(perDelivery) || perDelivery <= 0) {
                    rebuild(1, []);
                    return;
                }

                let previousDate = '';
                let html = '';
                for (let index = 0; index < count; index++) {
                    const saved = savedPlan[index] || {};
                    const defaultDate = addDays(deliveryDate.value, index);
                    const minimumDate = index === 0 ? deliveryDate.value : addDays(previousDate, 1);
                    let date = /^\d{4}-\d{2}-\d{2}$/.test(saved.date || '') ? saved.date : defaultDate;
                    if (date < minimumDate) date = minimumDate;
                    previousDate = date;

                    const palletCount = capacity > 0 ? Math.ceil(perDelivery / capacity) : 0;
                    const loadTotal = palletCount * capacity;
                    const availableCapacity = loadTotal - perDelivery;
                    const palletLabel = capacity > 0
                        ? `${palletCount.toLocaleString('pt-BR')} ${palletCount === 1 ? 'palete' : 'paletes'} de ${capacity.toLocaleString('pt-BR')}`
                        : 'Informe a capacidade no topo';
                    const capacityLabel = capacity > 0
                        ? (availableCapacity > 0
                            ? `Capacidade total: ${loadTotal.toLocaleString('pt-BR')} · Espaço livre: ${availableCapacity.toLocaleString('pt-BR')}`
                            : `Capacidade total: ${loadTotal.toLocaleString('pt-BR')} · Carga exata`)
                        : 'O cálculo será feito automaticamente.';

                    html += `<div class="card delivery-load-card mb-3" data-quantity="${perDelivery}">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                            <strong>Entrega ${index + 1} @includeIf('partials.change_marker')</strong>
                            <span class="badge badge-light delivery-schedule">${isCif ? 'Consultando entregas...' : 'Retirada pelo cliente (FOB)'}</span>
                        </div>
                        <div class="card-body py-3">
                            ${saved.id ? `<input type="hidden" class="delivery-plan-id" name="delivery_plan[${index}][id]" value="${Number(saved.id)}">` : ''}
                            <input type="hidden" name="delivery_plan[${index}][quantity]" value="${perDelivery}">
                            <input type="hidden" name="delivery_plan[${index}][palete_tipo][0]" value="${capacity || ''}">
                            <input type="hidden" name="delivery_plan[${index}][palete_quant][0]" value="${palletCount || ''}">
                            <div class="row align-items-start">
                                <div class="col-lg-3 col-md-4 form-group mb-md-0">
                                    <label>Quantidade da entrega</label>
                                    <input type="text" class="form-control delivery-calculated-value" value="${perDelivery.toLocaleString('pt-BR')}" readonly>
                                </div>
                                <div class="col-lg-3 col-md-4 form-group mb-md-0">
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
                                <div class="col-lg-6 col-md-4 form-group mb-0">
                                    <label>Paletes calculados</label>
                                    <input type="text" class="form-control delivery-calculated-value" value="${palletLabel}" readonly>
                                    <small class="form-text text-muted">${capacityLabel}</small>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }

                rowsContainer.innerHTML = html;
                rowsContainer.classList.remove('d-none');
                emptyMessage.classList.add('d-none');
                bindDateControls();
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

            // Mantém uma data diferente para cada entrega.
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
            palletCapacity.addEventListener('input', function() {
                render(Number(deliveryCount.value) || 1, currentPlan());
            });

            window.deliveryPlanEditor = { rebuild, setMinimumDate };
            rebuild(initialPlan.length || 1, initialPlan);
        })();
    </script>
@endpush
