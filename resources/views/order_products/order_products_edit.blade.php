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

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="product_name">Produto:</label>
                                <div class="form-control" readonly>{{ $order_product->product->name }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="quant">Quantidade</label>
                                @if ($order_product->quant > $saldo)
                                    <div class="form-control" readonly>
                                        {{ number_format($order_product->quant, 0, '', '.') }}</div>
                                @else
                                    <input type="text" name="quant" id="quant"
                                        class="form-control @error('quant') is-invalid @enderror qt"
                                        placeholder="Quantidade" value="{{ old('quant') ?? $order_product->quant }}">
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="delivery_date">Data de entrega</label>
                                @if (Auth::user()->is_admin)
                                    <input type="date" name="delivery_date" id="delivery_date"
                                        class="form-control @error('delivery_date') is-invalid @enderror"
                                        placeholder="Data de entrega"
                                        value="{{ old('delivery_date') ?? $order_product->delivery_date }}">
                                @else
                                    <div class="form-control" readonly>
                                        {{ date('d/m/Y', strtotime($order_product->delivery_date)) }}</div>
                                @endif
                            </div>

                            <div class="form-group">
                                <input type="checkbox" @if ($order_product->favorite_delivery) checked @endif
                                    name="favorite_delivery" id="favorite_delivery">
                                <label for="favorite_delivery">Fixar data</label>
                            </div>
                        </div>
                        <div class="col-sm">
                            <h6>Carga</h6>
                            <div class="row">
                                <div class="col-sm">
                                    <div class="form-group">
                                        <label>Palete (capacidade)</label>
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-1"
                                            class="form-control mb-2 blur-field" value="{{ $palete['tipo'][0] ?? 0 }}">
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-2"
                                            class="form-control mb-2 blur-field" value="{{ $palete['tipo'][1] ?? 0 }}">
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-3"
                                            class="form-control mb-2 blur-field" value="{{ $palete['tipo'][2] ?? 0 }}">
                                    </div>
                                </div>
                                <div class="col-sm">
                                    <div class="form-group">
                                        <label>Palete (quantidade)</label>
                                        <input type="number" name="palete_quant[]" id="palete_quant-1"
                                            class="form-control mb-2 blur-field" value="{{ $palete['quant'][0] ?? 0 }}">
                                        <input type="number" name="palete_quant[]" id="palete_quant-2"
                                            class="form-control mb-2 blur-field" value="{{ $palete['quant'][1] ?? 0 }}">
                                        <input type="number" name="palete_quant[]" id="palete_quant-3"
                                            class="form-control mb-2 blur-field" value="{{ $palete['quant'][2] ?? 0 }}">
                                        <hr>
                                        <div>Carga (total): <span id="palete_total_geral" class="form-control"
                                                readonly>0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm">
                                    <div class="form-group">
                                        <label>Palete (total)</label>
                                        <input type="text" name="palete_total[]" id="palete_total-1"
                                            class="form-control mb-2 blur-field">
                                        <input type="text" name="palete_total[]" id="palete_total-2"
                                            class="form-control mb-2 blur-field">
                                        <input type="text" name="palete_total[]" id="palete_total-3"
                                            class="form-control mb-2 blur-field">
                                        <hr>
                                        <div>Carga (diferença): <span id="palete_total_falta" class="form-control"
                                                readonly>0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

        var fixedDate = document.querySelector('#favorite_delivery');
        var deliveryDate = document.querySelector('#delivery_date');

        fixedDate.addEventListener('click', function() {
            if (this.checked) {
                deliveryDate.setAttribute('readonly', true);
            } else {
                deliveryDate.removeAttribute('readonly');
            }
        });

        if (deliveryDate) {
            if (fixedDate.checked) {
                deliveryDate.setAttribute('readonly', true);
            } else {
                deliveryDate.removeAttribute('readonly');
            }
        }

        // Calcular cargas
        const fields = document.querySelectorAll('.blur-field');
        const palete_tipo_1 = document.querySelector('#palete_tipo-1');
        const palete_tipo_2 = document.querySelector('#palete_tipo-2');
        const palete_tipo_3 = document.querySelector('#palete_tipo-3');
        const palete_quant_1 = document.querySelector('#palete_quant-1');
        const palete_quant_2 = document.querySelector('#palete_quant-2');
        const palete_quant_3 = document.querySelector('#palete_quant-3');
        const palete_total_1 = document.querySelector('#palete_total-1');
        const palete_total_2 = document.querySelector('#palete_total-2');
        const palete_total_3 = document.querySelector('#palete_total-3');
        var palete_total_geral = document.querySelector('#palete_total_geral');
        var palete_total_falta = document.querySelector('#palete_total_falta');

        fields.forEach(e => {
            e.addEventListener('blur', function() {
                calcular_carga();
            })
        });

        function calcular_carga() {
            var total_1 = 0;
            var total_2 = 0;
            var total_3 = 0;
            var total_geral = 0
            var total_falta = 0

            if (palete_tipo_1.value && palete_quant_1.value)
                total_1 = palete_tipo_1.value * palete_quant_1.value
            if (palete_tipo_2.value && palete_quant_2.value)
                total_2 = palete_tipo_2.value * palete_quant_2.value
            if (palete_tipo_3.value && palete_quant_3.value)
                total_3 = palete_tipo_3.value * palete_quant_3.value

            var quant_prod;
            if (document.querySelector('#quant'))
                quant_prod = document.querySelector('#quant').value.replace('.', '')
            else
                quant_prod = {{ $order_product->quant }}

            palete_total_1.value = total_1.toLocaleString('pt-BR')
            palete_total_2.value = total_2.toLocaleString('pt-BR')
            palete_total_3.value = total_3.toLocaleString('pt-BR')

            console.log(quant_prod, total_geral)

            total_geral = total_1 + total_2 + total_3
            total_falta = quant_prod - total_geral
            palete_total_geral.textContent = total_geral.toLocaleString('pt-BR')
            palete_total_falta.textContent = total_falta.toLocaleString('pt-BR')
        }

        calcular_carga();
    </script>
@endsection
