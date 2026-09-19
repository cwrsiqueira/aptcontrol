@extends('layouts.template')

@section('title', 'Produtos do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Cadastrar Produto do Pedido @includeIf('partials.change_marker')</h2>

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
                <form action="{{ route('order_products.store', ['order' => $order]) }}" method="post" novalidate>
                    @csrf

                    {{-- Reúne os dados principais antes do planejamento. --}}
                    <div class="row align-items-end">
                        <div class="col-lg-5 col-md-12">
                            <div class="form-group">
                                <label for="product_name">Produto <small>(Digite um novo nome para cadastrar)</small>:</label>
                                <input type="search" class="form-control @error('product_name') is-invalid @enderror"
                                    id="product_name" name="product_name" list="lista-produtos"
                                    placeholder="Busca produto..." value="{{ old('product_name') }}">
                                <datalist id="lista-produtos">
                                    @foreach ($products as $item)
                                        <option value="{{ $item->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <div class="form-group">
                                <label for="quant">Quantidade</label>
                                <input type="text" name="quant" id="quant"
                                    class="form-control @error('quant') is-invalid @enderror qt"
                                    placeholder="Quantidade" value="{{ old('quant') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <div class="form-group">
                                <label for="pallet_capacity">Capacidade do palete</label>
                                <input type="number" name="pallet_capacity" id="pallet_capacity" min="1" required
                                    class="form-control @error('pallet_capacity') is-invalid @enderror"
                                    placeholder="Ex.: 520" value="{{ old('pallet_capacity') }}">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <div class="form-group">
                                <label for="delivery_date">Previsão mínima de entrega</label>
                                <input type="date" name="delivery_date" id="delivery_date"
                                    class="form-control @error('delivery_date') is-invalid @enderror"
                                    value="{{ old('delivery_date') ?? date('Y-m-d', strtotime('+1 day')) }}" readonly>
                            </div>
                        </div>
                    </div>

                    {{-- Exibe o planejamento simplificado do cadastro. --}}
                    @include('order_products._delivery_plan_create_editor')

                    <button class="btn btn-primary" id="btn-salvar">Salvar</button>
                    <a class="btn btn-light" href="{{ route('order_products.index', ['order' => $order]) }}">Cancelar</a>
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

        const form = document.querySelector('form');
        const product = document.querySelector('#product_name');
        const quant = document.querySelector('#quant');
        const saveButton = document.querySelector('#btn-salvar');
        let submitting = false;

        // Atualiza a primeira data conforme produto e quantidade.
        function updateMinimumDeliveryDate() {
            if (!product.value || !quant.value) return $.Deferred().resolve().promise();

            return $.ajax({
                url: "{{ route('get_data_product') }}",
                method: 'GET',
                data: { product: product.value },
                dataType: 'json'
            }).then(resp => {
                if (!resp || !resp.id) return null;
                return $.ajax({
                    url: "{{ route('day_delivery_calc') }}",
                    method: 'GET',
                    data: { id: resp.id, quant: quant.value },
                    dataType: 'json'
                });
            }).then(date => {
                if (date) window.deliveryPlanEditor.setMinimumDate(date);
            });
        }

        product.addEventListener('blur', updateMinimumDeliveryDate);
        quant.addEventListener('blur', updateMinimumDeliveryDate);

        form.addEventListener('submit', function(event) {
            if (submitting) return;
            event.preventDefault();
            if (!product.value || !Number(String(quant.value).replace(/\D/g, ''))) {
                alert('Informe o produto e a quantidade.');
                return;
            }

            saveButton.disabled = true;
            updateMinimumDeliveryDate()
                .done(() => {
                    submitting = true;
                    form.submit();
                })
                .fail(() => {
                    saveButton.disabled = false;
                    alert('Não foi possível calcular a previsão de entrega. Tente novamente.');
                });
        });
    </script>
    @stack('js')
@endsection
