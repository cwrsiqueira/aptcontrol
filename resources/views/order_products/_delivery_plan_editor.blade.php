{{-- Recalcula as entregas pela quantidade de cada lote. --}}
@php
    $initialDeliveryPlan = old('delivery_plan', $deliveryPlan ?? []);
@endphp

<div class="card bg-light border mt-3 mb-3" data-delivery-plan-editor
    data-initial-plan="{{ base64_encode(json_encode($initialDeliveryPlan)) }}"
    data-is-cif="{{ strtolower($order->withdraw) === 'entregar' ? '1' : '0' }}"
    data-schedule-url="{{ route('order_products.truck_availability') }}">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div class="mr-3">
                <h6 class="mb-1">Fracionamento e carga por entrega @includeIf('partials.change_marker')</h6>
                <small class="text-muted">O sistema calcula quantas entregas são necessárias. A última fica com a sobra. Não há entrega no domingo, e a quantidade de cada entrega pode ser ajustada.</small>
            </div>
            <div class="form-group mb-0 mt-2 mt-sm-0 delivery-count-field">
                <label for="delivery_count_summary" class="mb-1">Entregas</label>
                <input type="text" id="delivery_count_summary" class="form-control form-control-sm" readonly value="">
            </div>
        </div>

        <div id="delivery_plan_empty" class="alert alert-secondary mb-0">
            Informe a quantidade e a quantidade por entrega para montar o planejamento.
        </div>
        <div id="delivery_plan_rows" class="d-none"></div>
    </div>
</div>

@push('css')
    <style>
        .delivery-count-field { min-width: 280px; }
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
            const perDeliveryField = document.querySelector('#quantity_per_delivery');
            const deliveryDate = document.querySelector('#delivery_date');
            const summary = document.querySelector('#delivery_count_summary');
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

            function isSunday(date) {
                const parts = String(date || '').split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) return false;
                return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2])).getUTCDay() === 0;
            }

            function nextBusinessDay(date) {
                return isSunday(date) ? addDays(date, 1) : date;
            }

            function businessDayAfter(date) {
                return nextBusinessDay(addDays(date, 1));
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

            function deliveryLots(total, perDelivery) {
                if (total <= 0 || perDelivery <= 0) return [];
                if (perDelivery >= total) return [total];

                const fullCount = Math.floor(total / perDelivery);
                const remainder = total % perDelivery;
                const lots = Array(fullCount).fill(perDelivery);
                if (remainder > 0) lots.push(remainder);
                return lots;
            }

            function summaryText(lots) {
                if (!lots.length) return '';
                const label = lots.length === 1 ? 'entrega' : 'entregas';
                if (lots.every(lot => lot === lots[0])) {
                    return `${lots.length.toLocaleString('pt-BR')} ${label} de ${lots[0].toLocaleString('pt-BR')}`;
                }

                return `${lots.length.toLocaleString('pt-BR')} ${label}`;
            }

            function sameLots(lots, plan) {
                return lots.length === plan.length && lots.every((lot, index) => Number(plan[index].quantity) === lot);
            }

            function palletSplit(quantity, capacity) {
                const qty = Number(quantity) || 0;
                const cap = Number(capacity) || 0;
                if (qty <= 0) return { types: [], counts: [] };
                if (cap < 1 || cap >= qty) return { types: [qty], counts: [1] };

                const full = Math.floor(qty / cap);
                const remainder = qty % cap;
                if (remainder === 0) return { types: [cap], counts: [full] };
                return { types: [cap, remainder], counts: [full, 1] };
            }

            function palletSummary(types, counts) {
                const parts = [];
                types.forEach((type, index) => {
                    const capacity = Number(type) || 0;
                    const count = Number(counts[index]) || 0;
                    if (capacity > 0 && count > 0) {
                        parts.push(`${count.toLocaleString('pt-BR')} ${count === 1 ? 'palete' : 'paletes'} de ${capacity.toLocaleString('pt-BR')}`);
                    }
                });
                return parts.length ? `${parts.join(' e ')}.` : '';
            }

            function inferCapacity(item, quantity) {
                const types = (item?.palete_tipo || []).map(Number).filter(type => type > 0);
                const counts = (item?.palete_quant || []).map(Number);
                if (types.length === 1 && counts[0] > 1 && types[0] * counts[0] === quantity) return types[0];
                if (types.length >= 2) return Math.max(...types);
                return 0;
            }

            function typedCapacity(item) {
                if (!item || !Object.prototype.hasOwnProperty.call(item, 'pallet_capacity')) return null;
                if (item.pallet_capacity === null || item.pallet_capacity === '') return null;
                return Number(item.pallet_capacity) || 0;
            }

            function rowCapacity(saved, original, quantity) {
                const typed = typedCapacity(saved);
                if (typed !== null) return typed;
                if ((saved?.palete_tipo || []).some(type => Number(type) > 0)) {
                    return inferCapacity(saved, quantity);
                }
                return inferCapacity(original, quantity);
            }

            function readCapacity(card) {
                const value = card.querySelector('.delivery-pallet-capacity')?.value;
                if (value === undefined || String(value).trim() === '') return null;
                return parseQuantity(value);
            }

            function showMessage(message) {
                summary.value = '';
                rowsContainer.classList.add('d-none');
                rowsContainer.innerHTML = '';
                emptyMessage.textContent = message;
                emptyMessage.classList.remove('d-none');
            }

            function currentPlan() {
                return Array.from(rowsContainer.querySelectorAll('.delivery-load-card')).map(card => ({
                    id: Number(card.querySelector('.delivery-plan-id')?.value) || null,
                    date: card.querySelector('.delivery-plan-date')?.value || '',
                    quantity: Number(card.dataset.quantity) || 0,
                    pallet_capacity: readCapacity(card),
                    palete_tipo: Array.from(card.querySelectorAll('input[name*="[palete_tipo]"]')).map(field => field.value),
                    palete_quant: Array.from(card.querySelectorAll('input[name*="[palete_quant]"]')).map(field => field.value)
                }));
            }

            function resolveDate(index, savedDate, previousDate) {
                const minimumDate = index === 0
                    ? nextBusinessDay(deliveryDate.value)
                    : businessDayAfter(previousDate);
                let date = /^\d{4}-\d{2}-\d{2}$/.test(savedDate || '') ? savedDate : minimumDate;
                if (!date || isSunday(date) || date < minimumDate) date = minimumDate;
                return { date, minimumDate };
            }

            function render(lots, savedPlan = [], editableFrom = null) {
                const keepSavedPallets = editableFrom === null && sameLots(lots, initialPlan);
                let previousDate = '';
                let html = '';

                lots.forEach((lot, index) => {
                    const saved = savedPlan[index] || {};
                    const original = initialPlan[index] || {};
                    const resolved = resolveDate(index, saved.date, previousDate);
                    const date = resolved.date;
                    const minimumDate = resolved.minimumDate;
                    previousDate = date;
                    const savedQuantity = Number(saved.quantity) || Number(original.quantity) || 0;
                    const keepRowPallets = editableFrom !== null
                        && index < editableFrom
                        && savedQuantity === lot
                        && (saved.palete_tipo || []).some(type => Number(type) > 0);
                    const capacity = rowCapacity(saved, original, lot);
                    const clearedCapacity = typedCapacity(saved) === 0;
                    let types;
                    let counts;
                    if (capacity > 0) {
                        const split = palletSplit(lot, capacity);
                        types = split.types;
                        counts = split.counts;
                    } else if (!clearedCapacity && keepRowPallets) {
                        types = saved.palete_tipo;
                        counts = saved.palete_quant;
                    } else if (!clearedCapacity && keepSavedPallets && (original.palete_tipo || []).length) {
                        types = original.palete_tipo;
                        counts = original.palete_quant;
                    } else {
                        types = [lot];
                        counts = [1];
                    }
                    const rowId = saved.id || original.id;
                    const isRemainder = index === lots.length - 1 && lot !== lots[0];
                    let palletFields = '';
                    types.forEach((type, slot) => {
                        const capacity = Number(type) || 0;
                        const count = Number(counts[slot]) || 0;
                        if (capacity <= 0 || count <= 0) return;
                        palletFields += `<input type="hidden" name="delivery_plan[${index}][palete_tipo][${slot}]" value="${capacity}">`;
                        palletFields += `<input type="hidden" name="delivery_plan[${index}][palete_quant][${slot}]" value="${count}">`;
                    });

                    html += `<div class="card delivery-load-card mb-3" data-quantity="${lot}">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                            <strong>Entrega ${index + 1}${isRemainder ? ' (sobra)' : ''} @includeIf('partials.change_marker')</strong>
                            <span class="badge badge-light delivery-schedule">${isCif ? 'Consultando entregas...' : 'Retirada pelo cliente (FOB)'}</span>
                        </div>
                        <div class="card-body py-3">
                            ${rowId ? `<input type="hidden" class="delivery-plan-id" name="delivery_plan[${index}][id]" value="${Number(rowId)}">` : ''}
                            <input type="hidden" name="delivery_plan[${index}][quantity]" value="${lot}">
                            ${palletFields}
                            <div class="row align-items-start">
                                <div class="col-lg-3 col-md-4 form-group mb-md-0">
                                    <label>Quantidade da entrega</label>
                                    <input type="text" class="form-control delivery-quantity-input" value="${lot.toLocaleString('pt-BR')}" inputmode="numeric">
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
                                        value="${date}" data-minimum-date="${minimumDate}" data-accepted-date="${date}">
                                </div>
                                <div class="col-lg-6 col-md-4 form-group mb-0">
                                    <label>Capacidade do palete</label>
                                    <input type="text" class="form-control delivery-pallet-capacity" name="delivery_plan[${index}][pallet_capacity]"
                                        value="${capacity > 0 ? capacity : ''}" inputmode="numeric" placeholder="Ex.: 261">
                                    <small class="form-text text-muted delivery-pallet-summary">${palletSummary(types, counts)}</small>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });

                rowsContainer.innerHTML = html;
                rowsContainer.classList.remove('d-none');
                emptyMessage.classList.add('d-none');
                summary.value = summaryText(lots);
                bindDateControls();
                bindQuantityControls();
                bindCapacityControls();
                refreshSchedule();
            }

            function bindCapacityControls() {
                rowsContainer.querySelectorAll('.delivery-pallet-capacity').forEach(field => {
                    field.addEventListener('input', function() {
                        updatePalletFields(this.closest('.delivery-load-card'), parseQuantity(this.value));
                    });
                    field.addEventListener('blur', function() {
                        const capacity = parseQuantity(this.value);
                        this.value = capacity > 0 ? String(capacity) : '';
                        updatePalletFields(this.closest('.delivery-load-card'), capacity);
                    });
                });
            }

            function updatePalletFields(card, capacity) {
                const quantity = Number(card.dataset.quantity) || 0;
                const split = palletSplit(quantity, capacity);
                const named = card.querySelector('[name^="delivery_plan["]');
                const index = named?.name.match(/delivery_plan\[(\d+)\]/)?.[1] || '0';
                card.querySelectorAll('input[name*="[palete_tipo]"], input[name*="[palete_quant]"]').forEach(field => field.remove());
                let palletFields = '';
                split.types.forEach((type, slot) => {
                    palletFields += `<input type="hidden" name="delivery_plan[${index}][palete_tipo][${slot}]" value="${type}">`;
                    palletFields += `<input type="hidden" name="delivery_plan[${index}][palete_quant][${slot}]" value="${split.counts[slot]}">`;
                });
                card.querySelector('input[name*="[quantity]"]').insertAdjacentHTML('afterend', palletFields);
                card.querySelector('.delivery-pallet-summary').textContent = palletSummary(split.types, split.counts);
            }

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
                        recalculateDates({ currentTarget: hidden, manual: true });
                    });
                });

                rowsContainer.querySelectorAll('.delivery-plan-date-picker').forEach(field => {
                    field.addEventListener('change', function() {
                        const hidden = this.closest('.form-group').querySelector('.delivery-plan-date');
                        hidden.value = this.value;
                        recalculateDates({ currentTarget: hidden, manual: true });
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

            function recalculateDates(event) {
                const fields = Array.from(rowsContainer.querySelectorAll('.delivery-plan-date'));
                const changedIndex = fields.indexOf(event.currentTarget);
                if (changedIndex < 0) return;

                const minimumDate = changedIndex === 0
                    ? nextBusinessDay(deliveryDate.value)
                    : businessDayAfter(fields[changedIndex - 1].value);
                const chosen = fields[changedIndex].value;

                if (event.manual && isSunday(chosen)) {
                    alert('Não é possível agendar entrega no domingo.');
                    fields[changedIndex].value = fields[changedIndex].dataset.acceptedDate || minimumDate;
                } else if (!chosen || chosen < minimumDate || isSunday(chosen)) {
                    fields[changedIndex].value = minimumDate;
                }

                fields[changedIndex].dataset.acceptedDate = fields[changedIndex].value;

                for (let index = changedIndex + 1; index < fields.length; index++) {
                    fields[index].value = businessDayAfter(fields[index - 1].value);
                    fields[index].dataset.acceptedDate = fields[index].value;
                }

                fields.forEach((field, index) => {
                    field.dataset.minimumDate = index === 0
                        ? nextBusinessDay(deliveryDate.value)
                        : businessDayAfter(fields[index - 1].value);
                    field.dataset.acceptedDate = field.value;
                });

                syncDateControls();
                refreshSchedule();
            }

            function bindQuantityControls() {
                rowsContainer.querySelectorAll('.delivery-quantity-input').forEach((field, index) => {
                    field.addEventListener('focus', function() {
                        this.select();
                    });
                    field.addEventListener('blur', function() {
                        adjustFrom(index, parseQuantity(this.value));
                    });
                });
            }

            function adjustFrom(index, value) {
                const total = parseQuantity(quant.value);
                const perDelivery = parseQuantity(perDeliveryField.value);
                const saved = currentPlan();
                if (value === saved[index]?.quantity) return;
                const previousSum = saved.slice(0, index).reduce((sum, item) => sum + item.quantity, 0);
                const available = total - previousSum;

                if (value < 1 || value > available) {
                    alert(value > available
                        ? `A quantidade não pode passar de ${available.toLocaleString('pt-BR')}.`
                        : 'Informe uma quantidade válida.');
                    render(saved.map(item => item.quantity), saved);
                    return;
                }

                const tail = deliveryLots(available - value, perDelivery);
                const lots = saved.slice(0, index).map(item => item.quantity).concat([value], tail);
                if (lots.length > maxDeliveries) {
                    alert('A quantidade por entrega gera mais de 100 entregas. Aumente a quantidade por entrega.');
                    render(saved.map(item => item.quantity), saved);
                    return;
                }

                render(lots, saved, index);
            }

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

            function rebuild(savedPlan) {
                const total = parseQuantity(quant.value);
                const perDelivery = parseQuantity(perDeliveryField.value);
                const lots = deliveryLots(total, perDelivery);

                if (!lots.length) {
                    showMessage('Informe a quantidade e a quantidade por entrega para montar o planejamento.');
                    return;
                }

                if (lots.length > maxDeliveries) {
                    showMessage('A quantidade por entrega gera mais de 100 entregas. Aumente a quantidade por entrega.');
                    return;
                }

                const dates = savedPlan || currentPlan();
                const aligned = dates.length === lots.length
                    ? dates
                    : (initialPlan.length === lots.length ? initialPlan : []);
                render(lots, lots.map((_, index) => {
                    const row = { ...(aligned[index] || {}) };
                    if (dates[index] && Object.prototype.hasOwnProperty.call(dates[index], 'pallet_capacity')) {
                        row.pallet_capacity = dates[index].pallet_capacity;
                    }
                    return row;
                }));
            }

            function setMinimumDate(date) {
                deliveryDate.value = date;
                const plan = currentPlan();
                const used = new Set();
                let previous = '';
                plan.forEach((item, index) => {
                    const minimum = index === 0 ? nextBusinessDay(date) : businessDayAfter(previous);
                    if (!item.date || item.date < minimum || isSunday(item.date) || used.has(item.date)) {
                        item.date = minimum;
                    }
                    while (used.has(item.date) || isSunday(item.date)) item.date = businessDayAfter(item.date);
                    used.add(item.date);
                    previous = item.date;
                });
                rebuild(plan);
            }

            quant.addEventListener('blur', function() {
                rebuild(currentPlan());
            });
            perDeliveryField.addEventListener('input', function() {
                rebuild(currentPlan());
            });

            window.deliveryPlanEditor = { rebuild, setMinimumDate };
            rebuild(initialPlan);
        })();
    </script>
@endpush
