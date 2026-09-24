{{-- Campos compartilhados pelo cadastro e pela edição. --}}
@php
    $formItems = old('items');
    if ($formItems === null) {
        $formItems = $purchase->exists
            ? $purchase->items->map(function ($item) {
                return ['description' => $item->description, 'quantity' => $item->quantity];
            })->values()->all()
            : [['description' => '', 'quantity' => '']];
    }
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Solicitação</strong></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="requester_id">Solicitante *</label>
                <select class="form-control @error('requester_id') is-invalid @enderror" id="requester_id" name="requester_id" required>
                    <option value="">Selecione</option>
                    @foreach ($requesters as $requester)
                        <option value="{{ $requester->id }}"
                            @if ((string) old('requester_id', $purchase->requester_id ?: Auth::id()) === (string) $requester->id) selected @endif>
                            {{ $requester->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6">
                <label for="request_date">Data da solicitação *</label>
                <input type="date" class="form-control @error('request_date') is-invalid @enderror"
                    id="request_date" name="request_date" required
                    value="{{ old('request_date', $purchase->request_date ? $purchase->request_date->format('Y-m-d') : now()->toDateString()) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Descrição ou justificativa *</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                rows="3" maxlength="2000" required>{{ old('description', $purchase->description) }}</textarea>
        </div>

        <div class="form-group mb-0">
            <label for="notes">Observações</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                rows="2" maxlength="2000">{{ old('notes', $purchase->notes) }}</textarea>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Itens solicitados</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-purchase-item">
            <i class="fas fa-plus"></i> Adicionar item
        </button>
    </div>
    <div class="card-body" id="purchase-items">
        @foreach ($formItems as $index => $item)
            <div class="form-row purchase-item align-items-end mb-2">
                <div class="form-group col-md-8 mb-0">
                    <label>Descrição *</label>
                    <input type="text" class="form-control" name="items[{{ $index }}][description]"
                        maxlength="255" required value="{{ $item['description'] ?? '' }}">
                </div>
                <div class="form-group col-md-3 mb-0">
                    <label>Quantidade *</label>
                    <input type="number" class="form-control" name="items[{{ $index }}][quantity]"
                        min="0.001" step="0.001" required value="{{ $item['quantity'] ?? '' }}">
                </div>
                <div class="form-group col-md-1 mb-0">
                    <button type="button" class="btn btn-outline-danger btn-block remove-purchase-item" title="Remover">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<button type="submit" class="btn btn-primary">Salvar</button>
<a class="btn btn-light" href="{{ $purchase->exists ? route('purchases.show', $purchase) : route('purchases.index') }}">Cancelar</a>

@section('js')
    <script>
        $(function() {
            function reindexItems() {
                $('#purchase-items .purchase-item').each(function(index) {
                    $(this).find('input').each(function() {
                        this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                    });
                });

                $('.remove-purchase-item').prop('disabled', $('#purchase-items .purchase-item').length === 1);
            }

            $('#add-purchase-item').on('click', function() {
                $('#purchase-items').append(`
                    <div class="form-row purchase-item align-items-end mb-2">
                        <div class="form-group col-md-8 mb-0">
                            <label>Descrição *</label>
                            <input type="text" class="form-control" name="items[0][description]" maxlength="255" required>
                        </div>
                        <div class="form-group col-md-3 mb-0">
                            <label>Quantidade *</label>
                            <input type="number" class="form-control" name="items[0][quantity]" min="0.001" step="0.001" required>
                        </div>
                        <div class="form-group col-md-1 mb-0">
                            <button type="button" class="btn btn-outline-danger btn-block remove-purchase-item" title="Remover">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                `);
                reindexItems();
            });

            $('#purchase-items').on('click', '.remove-purchase-item', function() {
                if ($('#purchase-items .purchase-item').length > 1) {
                    $(this).closest('.purchase-item').remove();
                    reindexItems();
                }
            });

            reindexItems();
        });
    </script>
@endsection
