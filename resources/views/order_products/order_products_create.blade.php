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

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="product_name">Produto <small>(Digite um novo nome para
                                        cadastrar)</small>:</label>
                                <input type="search" class="form-control @error('product_name') is-invalid @enderror"
                                    id="product_name" name="product_name" list="lista-produtos"
                                    placeholder="Busca produto..." value="{{ old('product_name') }}">
                                <datalist id="lista-produtos">
                                    @foreach ($products as $item)
                                        <option @if (old('product_name') == $item->name) selected @endif
                                            value="{{ $item->name }}">
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
                                    class="form-control @error('delivery_date') is-invalid @enderror"
                                    placeholder="Data de entrega"
                                    value="{{ old('delivery_date') ?? date('Y-m-d', strtotime('+1 day')) }}" readonly>
                            </div>
                        </div>
                        <div class="col-sm">
                            <h6>Carga</h6>
                            <div class="row">
                                <div class="col-sm">
                                    <div class="form-group">
                                        <label>Palete (capacidade)</label>
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-1"
                                            class="form-control mb-2 blur-field">
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-2"
                                            class="form-control mb-2 blur-field">
                                        <input type="number" name="palete_tipo[]" id="palete_tipo-3"
                                            class="form-control mb-2 blur-field">
                                    </div>
                                </div>
                                <div class="col-sm">
                                    <div class="form-group">
                                        <label>Palete (quantidade)</label>
                                        <input type="number" name="palete_quant[]" id="palete_quant-1"
                                            class="form-control mb-2 blur-field">
                                        <input type="number" name="palete_quant[]" id="palete_quant-2"
                                            class="form-control mb-2 blur-field">
                                        <input type="number" name="palete_quant[]" id="palete_quant-3"
                                            class="form-control mb-2 blur-field">
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
            } else {
                alert('Todos os campos são obrigatórios!');
                window.location.reload();
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

                const quant_prod = quant.value.replace('.', '')

                palete_total_1.value = total_1.toLocaleString('pt-BR')
                palete_total_2.value = total_2.toLocaleString('pt-BR')
                palete_total_3.value = total_3.toLocaleString('pt-BR')

                total_geral = total_1 + total_2 + total_3
                total_falta = quant_prod - total_geral
                palete_total_geral.textContent = total_geral.toLocaleString('pt-BR')
                palete_total_falta.textContent = total_falta.toLocaleString('pt-BR')
            })
        });

        function bestPalletCombo(sizes, target) {
            const [aSize, bSize, cSize] = sizes.slice().sort((x, y) => x - y); // 260, 325, 390
            let best = {
                counts: [0, 0, 0],
                total: 0,
                diff: Infinity,
                exact: false,
                pallets: Infinity,
                over: false
            };

            // limite superior simples (até passar o alvo com o menor palete)
            const maxA = Math.ceil(target / aSize) + 5;
            const maxB = Math.ceil(target / bSize) + 5;
            const maxC = Math.ceil(target / cSize) + 5;

            for (let c = 0; c <= maxC; c++) {
                const partC = c * cSize;
                if (partC > target + best.diff) break; // pequeno corte: se já passou demais
                for (let b = 0; b <= maxB; b++) {
                    const partB = partC + b * bSize;
                    if (partB > target + best.diff) break;

                    // restante para A; testamos arredores (floor/ceil)
                    const remaining = target - partB;
                    const aFloor = Math.max(0, Math.floor(remaining / aSize));
                    const aCandidates = new Set([aFloor, aFloor + 1, aFloor + 2]); // checa um pouco acima tb

                    for (const a of aCandidates) {
                        if (a < 0 || a > maxA) continue;
                        const total = a * aSize + b * bSize + c * cSize;
                        const diff = Math.abs(total - target);
                        const pallets = a + b + c;
                        const over = total > target;

                        // Regras de escolha
                        let better = false;
                        if (diff < best.diff) better = true;
                        else if (diff === best.diff && pallets < best.pallets) better = true;
                        else if (diff === best.diff && pallets === best.pallets) {
                            // prefere não exceder o alvo
                            if (best.over && !over) better = true;
                        }

                        if (better) {
                            best = {
                                counts: [a, b, c],
                                total,
                                diff,
                                exact: diff === 0,
                                pallets,
                                over
                            };
                            if (best.exact) return best; // achou exato: pode retornar
                        }
                    }
                }
            }
            return best;
        }
    </script>
@endsection
