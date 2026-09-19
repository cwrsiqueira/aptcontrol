@extends('layouts.template')

@section('title', 'Produtos do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Editar Produto do Pedido @includeIf('partials.change_marker')</h2>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('order_products.update', ['order_product' => $order_product]) }}" method="post" novalidate>
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Produto</label>
                                <div class="form-control" readonly>{{ $order_product->product->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="quant">Quantidade</label>
                                @if ($order_product->quant > $saldo)
                                    <input type="hidden" name="quant" id="quant" value="{{ $order_product->quant }}">
                                    <div class="form-control" readonly>{{ number_format($order_product->quant, 0, '', '.') }}</div>
                                @else
                                    <input type="text" name="quant" id="quant"
                                        class="form-control @error('quant') is-invalid @enderror qt"
                                        placeholder="Quantidade" value="{{ old('quant') ?? $order_product->quant }}">
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="delivery_date">Previsão mínima de entrega</label>
                                @if (Auth::user()->is_admin)
                                    <input type="date" name="delivery_date" id="delivery_date"
                                        class="form-control @error('delivery_date') is-invalid @enderror"
                                        value="{{ old('delivery_date') ?? date('Y-m-d', strtotime($order_product->delivery_date)) }}">
                                @else
                                    <input type="hidden" name="delivery_date" id="delivery_date"
                                        value="{{ date('Y-m-d', strtotime($order_product->delivery_date)) }}">
                                    <div class="form-control" readonly>{{ date('d/m/Y', strtotime($order_product->delivery_date)) }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group pt-md-4">
                                <input type="checkbox" @if (old('favorite_delivery', $order_product->favorite_delivery)) checked @endif
                                    name="favorite_delivery" id="favorite_delivery">
                                <label for="favorite_delivery">Fixar data</label>
                            </div>
                        </div>
                    </div>

                    {{-- Permite ajustar cada entrega planejada. --}}
                    @include('order_products._delivery_plan_editor')

                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light" href="{{ route('order_products.index', ['order' => $order->id]) }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection

@section('css')
    @stack('css')
@endsection

@section('js')
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>
    <script>
        $('.qt').mask('000.000.000', { reverse: true });

        const fixedDate = document.querySelector('#favorite_delivery');
        const deliveryDate = document.querySelector('#delivery_date');

        function updateDateLock() {
            if (deliveryDate.type !== 'hidden') deliveryDate.readOnly = fixedDate.checked;
        }

        fixedDate.addEventListener('change', updateDateLock);
        deliveryDate.addEventListener('change', function() {
            window.deliveryPlanEditor.setMinimumDate(this.value);
        });
        updateDateLock();
    </script>
    @stack('js')
@endsection
