@extends('layouts.estilos')

@section('title', 'Entregas por Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas por Produto</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_voltar" href="{{ route('products.index') }}">
                    < Produtos</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- RESUMO DO PRODUTO --}}
            <div class="col-lg">
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
                        <div class="d-flex align-items-center">
                            <i class="icon fas fa-exclamation-triangle mr-2"></i>
                            <strong class="mr-2">OBS:</strong>
                            <span class="meta-chip mt-3">Clique na linha do pedido para marcar o pedido e
                                incluir
                                observações.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg">
                <form method="get" action="{{ route('cc_product', ['id' => $product->id]) }}">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros por legendas</strong>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="mb-0 d-flex align-items-center btn btn-sm btn-warning">
                                        <input class="mr-2" type="checkbox" name="por_favorito[]" value="1"
                                            @if (!empty($_GET['por_favorito']) && in_array(1, $_GET['por_favorito'])) checked @endif>
                                        <span class="text-truncate" title="Aguardando antecipação">Aguardando
                                            antecipação</span>
                                        <span
                                            class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[1] ?? 0, 0, '', '.') }}</span>
                                    </label>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="mb-0 d-flex align-items-center btn btn-sm btn-success">
                                        <input class="mr-2" type="checkbox" name="por_favorito[]" value="2"
                                            @if (!empty($_GET['por_favorito']) && in_array(2, $_GET['por_favorito'])) checked @endif>
                                        <span class="text-truncate" title="Liberados para entrega">Liberados para
                                            entrega</span>
                                        <span
                                            class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[2] ?? 0, 0, '', '.') }}</span>
                                    </label>
                                </div>
                            </div>
                            <hr>

                            <div class="d-flex align-items-center">
                                <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                                <a href="{{ route('cc_product', ['id' => $product->id]) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="card card-lift mb-5">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            <th>Data</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Categoria</th>
                            <th class="text-right">Saldo</th>
                            <th>Vendedor</th>
                            <th>Data Entrega</th>
                            <th>Tipo Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            @if ($item->saldo > 0)
                                <tr class="linha" data-id="{{ $item->id }}">
                                    <td>{{ date('d/m/Y', strtotime($item->order->order_date)) }}</td>
                                    <td>
                                        <a
                                            href="{{ route('order_products.index', ['order' => $item->order->id]) }}">#{{ $item->order_id }}</a>
                                    </td>
                                    <td
                                        class="@if ($item->checkmark == 1) btn btn-sm btn-warning p-0 px-1 @elseif($item->checkmark == 2) btn btn-sm btn-success p-0 px-1 @endif">
                                        {{ $item->order->client->name }}
                                    </td>
                                    <td>{{ $item->order->client->category->name }}</td>
                                    <td class="text-right">
                                        {{ number_format($item->saldo > $item->quant ? $item->quant : $item->saldo, 0, '', '.') }}
                                    </td>
                                    <td>{{ $item->order->seller->name ?? ' - ' }}</td>
                                    <td class="text-right d-flex flex-column align-items-end">
                                        {{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}
                                        <span
                                            class="btn btn-sm btn-danger p-0 px-1 @if (!$item->favorite_delivery) d-none @endif">Data
                                            fixada</span>
                                    </td>
                                    <td>
                                        @php $isCif = (Str::lower($item->order->withdraw) === 'entregar'); @endphp
                                        <span class="badge {{ $isCif ? 'badge-dark' : 'badge-info' }}">
                                            {{ Str::ucfirst($item->order->withdraw) }} ({{ $isCif ? 'CIF' : 'FOB' }})
                                        </span>
                                    </td>
                                </tr>
                            @endif
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

        .meta-chip {
            display: inline-block;
            padding: .2rem .5rem;
            font-size: .95rem;
            font-weight: 600;
            border-radius: .375rem;
            background: #f1f3f5;
            color: #495057;
            line-height: 1.1;
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
