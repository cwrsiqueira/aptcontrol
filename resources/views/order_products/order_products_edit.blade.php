@extends('layouts.template')

@section('title', 'Produtos do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Produto do Pedido</h2>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('order_products.update', ['order_product' => $order_product]) }}" method="post"
                    novalidate>
                    @method('PUT')
                    @csrf

                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                    <div class="form-group">
                        <label for="product_name">Produto:</label>
                        <div class="form-control" readonly>{{ $order_product->product->name }}</div>
                    </div>

                    <div class="form-group">
                        <label for="quant">Quantidade</label>
                        <input type="text" name="quant" id="quant"
                            class="form-control @error('quant') is-invalid @enderror qt" placeholder="Quantidade"
                            value="{{ old('quant') ?? $order_product->quant }}">
                    </div>

                    <div class="form-group">
                        <label for="delivery_date">Data de entrega</label>
                        <input type="date" name="delivery_date" id="delivery_date"
                            class="form-control @error('delivery_date') is-invalid @enderror" placeholder="Data de entrega"
                            value="{{ old('delivery_date') ?? $order_product->delivery_date }}">
                    </div>

                    <div class="form-group">
                        <input type="checkbox" @if ($order_product->favorite_delivery) checked @endif name="favorite_delivery"
                            id="favorite_delivery">
                        <label for="favorite_delivery">Fixar data</label>
                    </div>

                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light"
                        href="{{ route('order_products.index', ['order' => $order->id]) }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection

@section('js')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>p
    <script>
        $('.qt').mask('000.000.000', {
            reverse: true
        });
        var product = document.querySelector('#product_name');
        var quant = document.querySelector('#quant');
        product.addEventListener('blur', function() {
            if (product.value != '' && quant.value != '') {
                get_data_product(product.value, quant.value);
            }
        });
        quant.addEventListener('blur', function() {
            if (product.value != '' && quant.value != '') {
                get_data_product(product.value, quant.value);
            }
        });

        function get_data_product(product, quant) {
            $.ajax({
                url: "{{ route('get_data_product') }}",
                method: 'GET',
                data: {
                    product
                },
                dataType: 'json',
                success: function(resp) {
                    day_delivery_calc(resp.id, quant);
                },
                error: function(xhr, status, err) {
                    console.error('Erro', status, err, xhr.responseText);
                },
            });
        }

        function day_delivery_calc(id, quant) {
            $.ajax({
                url: "{{ route('day_delivery_calc') }}",
                method: 'GET',
                data: {
                    id,
                    quant
                },
                dataType: 'json',
                success: function(resp) {
                    document.querySelector('#delivery_date').value = resp;
                },
                error: function(xhr, status, err) {
                    console.error('Erro', status, err, xhr.responseText);
                },
            });
        }

        var fixedDate = document.querySelector('#favorite_delivery');
        var deliveryDate = document.querySelector('#delivery_date');

        fixedDate.addEventListener('click', function() {
            if (this.checked) {
                deliveryDate.setAttribute('readonly', true);
            } else {
                deliveryDate.removeAttribute('readonly');
            }
        });

        if (fixedDate.checked) {
            deliveryDate.setAttribute('readonly', true);
        } else {
            deliveryDate.removeAttribute('readonly');
        }
    </script>
@endsection
