@extends('layouts.estilos')

@section('title', 'Entregas do Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas do Produto</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_voltar" href="{{ route('products.index') }}">
                    < Produtos</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- RESUMO DO PRODUTO --}}
            <div class="col-lg-4">
                <div class="card card-lift mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold">Produto</span>
                        <span class="badge badge-primary badge-client-name">{{ $product->name }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="far fa-clipboard mr-2"></i>
                            <strong class="mr-2">Total a entregar:</strong>
                            <span>{{ number_format($quant_total ?? 0, 0, '', '.') }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="far fa-calendar-alt mr-2"></i>
                            <strong class="mr-2">Entregas a partir de:</strong>
                            <span>{{ date('d/m/Y', strtotime($delivery_in)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg-5">
                <form method="get" action="{{ route('cc_product', ['id' => $product->id]) }}">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros por categoria do cliente</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($quant_por_categoria as $item)
                                    <div class="col-md-6 mb-2">
                                        <label class="mb-0 d-flex align-items-center">
                                            <input class="mr-2" type="checkbox" name="por_categoria[]"
                                                value="{{ $item['id'] }}"
                                                @if (!empty($_GET['por_categoria']) && in_array($item['id'], $_GET['por_categoria'])) checked @endif>
                                            <span class="text-truncate"
                                                title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                                            <span
                                                class="ml-2 badge badge-light">{{ number_format($item['saldo'], 0, '', '.') }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <hr>

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="chk_entregas" name="entregas"
                                    value="1" @if (!empty($_GET['entregas'])) checked @endif>
                                <label class="custom-control-label" for="chk_entregas">Mostrar entregas realizadas</label>
                            </div>

                            <div class="d-flex align-items-center">
                                <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                                <a href="{{ route('cc_product', ['id' => $product->id]) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- LEGENDAS --}}
            <div class="col-lg-3 nao-imprimir">
                <div class="card card-lift mb-3">
                    <div class="card-header">
                        <strong>Legendas</strong>
                    </div>
                    <div class="card-body">
                        <div class="d-block mb-3">
                            <div class="fav-client is-fav-client d-inline-block">Cliente aguardando antecipação</div>
                        </div>
                        <div class="d-block">
                            <div class="fav-date is-fav-date d-inline-block">Data fixa para entrega</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="card card-lift mb-5">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Categoria</th>
                            <th>Pedido</th>
                            <th class="text-right">Saldo</th>
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
                                <td>
                                    <a
                                        href="{{ route('order_products.index', ['order' => $item->order->id]) }}">#{{ $item->order_id }}</a>
                                </td>
                                <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                <td>
                                    <a href="#"
                                        class="fav-date {{ (int) $item->favorite_delivery === 1 ? 'is-fav-date' : '' }}"
                                        data-op-id="{{ $item->id }}"
                                        data-url="{{ route('order_products.toggle_delivery_favorite', $item->id) }}">
                                        {{ date('d/m/Y', strtotime($item->delivery_date)) }}
                                    </a>
                                </td>
                                <td>
                                    @php $isCif = ($item->withdraw === 'Entregar'); @endphp
                                    <span class="badge {{ $isCif ? 'badge-success' : 'badge-info' }}">
                                        {{ $item->withdraw }} ({{ $isCif ? 'CIF' : 'FOB' }})
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- <div class="card-footer">{{ $data->links() }}</div> --}}
        </div>

        <hr>

    </main>
@endsection

@section('css')
    <style>
        /* elevação sutil nos cards */
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        /* tabela com cabeçalho grudado */
        .tableFixHead {
            max-height: 60vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* hover suave nas linhas */
        tbody tr:hover {
            background-color: #f6f9fc;
            cursor: pointer;
        }

        /* padrão: links de favorito como texto normal */
        a.fav-client,
        a.fav-date {
            color: inherit;
            text-decoration: none;
            font-weight: 400;
        }

        /* destaque CLIENTE (amarelo) */
        .is-fav-client {
            background: #ffde59;
            color: #111;
            font-weight: 700 !important;
            font-size: 1.06em;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* destaque DATA (roxo) */
        .is-fav-date {
            background: #dc3545;
            /* roxo bootstrap-ish */
            color: #fff !important;
            font-weight: 700 !important;
            font-size: 1.06em;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .page-header h2 {
            font-weight: 600;
        }

        .badge-client-name {
            font-size: .85rem;
            padding: .25rem .5rem;
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

        // Imprimir
        $('#btn_imprimir').click(function() {
            $(this).hide();
            $('#btn_voltar, #btn_sair, #btn_recalc, #search, #clean_search, .nao-imprimir').hide();
            window.print();
            history.go(0);
        });

        // Toggle favorito do CLIENTE (com confirmação)
        $(document).on('click', 'a.fav-client', function(e) {
            e.preventDefault();
            const $el = $(this);
            const elId = $el.data('client-id');
            const url = $el.data('url');

            const marcando = !$el.hasClass('is-fav-client');
            const msg = marcando ? 'Favoritar este cliente?' : 'Remover favorito deste cliente?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    // aplica no clicado
                    $el.toggleClass('is-fav-client', !!resp.is_favorite);
                    // aplica em TODOS com o mesmo data-client-id
                    $(`[data-client-id="${elId}"]`).toggleClass('is-fav-client', !!resp.is_favorite);
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar cliente', xhr.responseText);
                alert('Não foi possível alterar o favorito do cliente.');
            });
        });

        // Toggle favorito da DATA DE ENTREGA (com confirmação)
        $(document).on('click', 'a.fav-date', function(e) {
            e.preventDefault();
            var $el = $(this);
            var url = $el.data('url'); // rota /order-products/{id}/toggle-delivery-favorite
            var marcando = !$el.hasClass('is-fav-date');
            var msg = marcando ? 'Destacar esta DATA DE ENTREGA?' : 'Remover destaque desta DATA DE ENTREGA?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    $el.toggleClass('is-fav-date', !!resp.favorite_delivery);
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar data de entrega', xhr.responseText);
                alert('Não foi possível alterar o destaque da data de entrega.');
            });
        });
    </script>
@endsection
