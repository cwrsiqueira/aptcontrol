@extends('layouts.estilos')

@section('title', 'C/C Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Produto</h2>
        <div class="row">
            <div class="card col-md m-3">
                <div class="card-header"><span>{{ $product->name }}</span></div>
                <div class="card-body">
                    Total a entregar: {{ number_format($quant_total ?? 0, 0, '', '.') ?? 0 }} <br>
                    Entregas a partir de: {{ date('d/m/Y', strtotime($delivery_in)) }} <br>
                </div>
            </div>
            <form method="get">
                <div class="card m-3">
                    <div class="card-header">
                        Filtros
                    </div>

                    <div class="card-body">
                        @foreach ($quant_por_categoria as $item)
                            <input class="mr-1" type="checkbox" name="por_categoria[]" value="{{ $item['id'] }}"
                                @if (!empty($_GET['por_categoria']) && in_array($item['id'], $_GET['por_categoria'])) checked @endif>{{ $item['name'] }} =
                            {{ number_format($item['saldo'], 0, '', '.') }} <br>
                        @endforeach
                        <hr>
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{ route('cc_product', ['id' => $product->id]) }}" id="clean_search">Limpar Filtro</a>
                    </div>
                </div>
            </form>

            <div class="col-md m-3">
                <div class="row">
                    <div class="card-tools">
                        <a class="btn btn-sm btn-secondary" id="btn_voltar" href="{{ route('products.index') }}">Voltar</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="d-flex flex-column">
                        <div class="fav-client is-fav-client mb-3">Cliente aguardando antecipação</div>
                        <div class="fav-date is-fav-date">Data fixa para entrega</div>
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Categoria</th>
                    <th>Pedido</th>
                    <th>Saldo</th>
                    <th>Data Entrega</th>
                    <th>Tipo Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr class="linha" data-id="{{ $item->id }}">
                        <td>{{ date('d/m/Y', strtotime($item->order_date)) }}</td>
                        <td>
                            <a href="#"
                                class="fav-client {{ (int) $item->client_favorite === 1 ? 'is-fav-client' : '' }}"
                                data-client-id="{{ $item->client_id }}"
                                data-url="{{ route('clients.toggle_favorite', $item->client_id) }}">
                                {{ $item->client_name }}
                            </a>
                        </td>
                        <td>{{ $item->seller_name ?? ' - ' }}</td>
                        <td>{{ $item->category_name }}</td>
                        <td>{{ $item->order_id }}</td>
                        <td>{{ number_format($item->saldo, 0, '', '.') }}</td>
                        <td>
                            <a href="#"
                                class="fav-date {{ (int) $item->favorite_delivery === 1 ? 'is-fav-date' : '' }}"
                                data-op-id="{{ $item->id }}"
                                data-url="{{ route('order_products.toggle_delivery_favorite', $item->id) }}">
                                {{ date('d/m/Y', strtotime($item->delivery_date)) }}
                            </a>
                        </td>
                        <td>{{ $item->withdraw }} ({{ $item->withdraw === 'Entregar' ? 'CIF' : 'FOB' }})</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- {{$data->links()}} --}}
    </main>
@endsection

@section('css')
    <style>
        tbody tr:hover {
            background-color: rgb(227, 236, 233);
            cursor: pointer;
        }

        .hide {
            display: none;
        }

        /* Padrão: texto normal (sem cara de link) */
        a.fav-client,
        a.fav-date {
            color: inherit;
            text-decoration: none;
            font-weight: 400;
        }

        /* Destaque CLIENTE (amarelo forte/contrastante) */
        .is-fav-client {
            background: #ffde59;
            color: #111;
            font-weight: 700 !important;
            font-size: 1.06em;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Destaque DATA (azul/roxo forte, distinto do cliente) */
        .is-fav-date {
            background: #6f42c1;
            /* roxo bootstrap-ish */
            color: #fff !important;
            font-weight: 700 !important;
            font-size: 1.06em;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
@endsection

@section('js')
    <script>
        // CSRF para Ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        // Imprimir (mantém seu comportamento)
        $('#btn_imprimir').click(function() {
            $(this).hide();
            $('#btn_voltar, #btn_sair, #btn_recalc, #search, #clean_search').hide();
            window.print();
            javascript: history.go(0);
        });

        // Toggle favorito do CLIENTE (com confirmação)
        $(document).on('click', 'a.fav-client', function(e) {
            e.preventDefault();
            var $el = $(this);
            var url = $el.data('url');
            var marcando = !$el.hasClass('is-fav-client');
            var msg = marcando ? 'Favoritar este cliente?' : 'Remover favorito deste cliente?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    if (resp.is_favorite) {
                        $el.addClass('is-fav-client');
                    } else {
                        $el.removeClass('is-fav-client');
                    }
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar cliente', xhr.responseText);
                alert('Não foi possível alterar o favorito do cliente.');
            });
        });

        // Toggle favorito da DATA DE ENTREGA por item (com confirmação)
        $(document).on('click', 'a.fav-date', function(e) {
            e.preventDefault();
            var $el = $(this);
            var url = $el.data('url'); // rota /order-products/{id}/toggle-delivery-favorite
            var marcando = !$el.hasClass('is-fav-date');
            var msg = marcando ? 'Destacar esta DATA DE ENTREGA?' : 'Remover destaque desta DATA DE ENTREGA?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    if (resp.favorite_delivery) {
                        $el.addClass('is-fav-date');
                    } else {
                        $el.removeClass('is-fav-date');
                    }
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar data de entrega', xhr.responseText);
                alert('Não foi possível alterar o destaque da data de entrega.');
            });
        });
    </script>
@endsection
