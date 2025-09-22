@php
    // Espera $sellers (collection id/name) e $order (opcional)
    $currentSellerId = isset($order) ? (int) old('seller_id', $order->seller_id) : (int) old('seller_id');
@endphp

<div class="form-group">
    <label for="seller_id">Vendedor</label>
    <select name="seller_id" id="seller_id" class="form-control">
        <option value="">— Sem vendedor —</option>
        @foreach ($sellers as $s)
            <option value="{{ $s->id }}" {{ $currentSellerId === (int) $s->id ? 'selected' : '' }}>
                {{ $s->name }}
            </option>
        @endforeach
    </select>
</div>
