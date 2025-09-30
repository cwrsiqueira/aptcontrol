@extends('layouts.template')

@section('title', 'Produtos do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Cadastrar Produto do Pedido</h2>

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
                <form action="{{ route('order_products.store', ['order' => $order]) }}" method="post" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="product_name">Produto <small>(Digite um novo nome para cadastrar)</small>:</label>
                        <input type="search" class="form-control @error('product_name') is-invalid @enderror"
                            id="product_name" name="product_name" list="lista-produtos" placeholder="Busca produto..."
                            value="{{ old('product_name') }}">
                        <datalist id="lista-produtos">
                            @foreach ($products as $item)
                                <option @if (old('product_name') == $item->name) selected @endif value="{{ $item->name }}">
                                </option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label for="quant">Quantidade</label>
                        <input type="text" name="quant" id="quant"
                            class="form-control @error('quant') is-invalid @enderror qt" placeholder="Quantidade"
                            value="{{ old('quant') }}">
                    </div>

                    <div class="form-group">
                        <label for="delivery_date">Data de entrega</label>
                        <input type="date" name="delivery_date" id="delivery_date"
                            class="form-control @error('delivery_date') is-invalid @enderror" placeholder="Data de entrega"
                            value="{{ old('delivery_date') ?? date('Y-m-d', strtotime('+1 day')) }}" readonly>
                    </div>

                    <button class="btn btn-primary" id="btn-salvar">Salvar</button>
                    <a class="btn btn-light" href="{{ route('order_products.index', ['order' => $order]) }}">Cancelar</a>
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

        // Calcular e preencher data de entrega
        var product = document.querySelector('#product_name');
        var quant = document.querySelector('#quant');

        document.querySelector('#btn-salvar').addEventListener('click', function(e) {
            e.preventDefault();
            this.setAttribute('disabled', true);
            if (product.value != '' && quant.value != '') {
                get_data_product(product.value, quant.value);
                setTimeout(() => {
                    this.form.submit();
                }, 1000);
            }
        })

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
        //

        // Desabilita campo data para preenchimento
        document.querySelector('#favorite_delivery').addEventListener('change', function() {
            this.checked ?
                document.querySelector('#delivery_date').setAttribute('readonly', true) :
                document.querySelector('#delivery_date').removeAttribute('readonly')
        })
    </script>
@endsection
