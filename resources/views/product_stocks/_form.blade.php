<div class="form-group">
    <label>Estoque *</label>
    <input type="text" name="stock" class="form-control qt" value="{{ old('stock', $stock->stock ?? '') }}"
        min="0" required>
</div>

<div class="form-group">
    <label>Data (opcional)</label>
    <input type="date" name="stock_date" class="form-control"
        value="{{ old(
            'stock_date',
            isset($stock) && $stock->stock_date ? $stock->stock_date->format('Y-m-d') : now()->format('Y-m-d'),
        ) }}">
</div>

<div class="form-group">
    <label>Observação (opcional)</label>
    <input type="text" name="notes" class="form-control" maxlength="255"
        value="{{ old('notes', $stock->notes ?? 'Ajuste manual') }}"
        placeholder="Ex.: ajuste manual, inventário, correção...">
</div>

@section('js')
    <script>
        $('.qt').mask('000.000.000', {
            reverse: true
        });
    </script>
@endsection
