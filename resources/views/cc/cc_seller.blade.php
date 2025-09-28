@extends('layouts.estilos')

@section('title', 'Entregas por Vendedor')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas por Vendedor</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_voltar" href="{{ route('sellers.index') }}">
                    < Vendedores</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- RESUMO DO Vendedor --}}
            <div class="col-lg-4">
                <div class="card card-lift mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold">Vendedor: </span>
                        <span class="badge badge-primary badge-client-name">{{ $seller->name }}</span>
                    </div>
                    <div class="card-body">
                        <small class="text-muted nao-imprimir">Selecione filtros ao lado e veja as entregas abaixo.</small>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg-5">
                <form method="get" action="{{ route('cc_seller', $seller->id) }}">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros por produtos</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($product_total as $key => $value)
                                    <div class="col-md-6 mb-2">
                                        <label class="mb-0 d-flex align-items-center">
                                            <input class="mr-2" type="checkbox" name="por_produto[]"
                                                value="{{ $value['id'] }}"
                                                @if (!empty($_GET['por_produto']) && in_array($value['id'], $_GET['por_produto'])) checked @endif>
                                            <span class="text-truncate" title="{{ $key }}">Total de
                                                {{ $key }}</span>
                                            <span
                                                class="ml-2 badge badge-light">{{ number_format($value['qt'], 0, '', '.') }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <hr>

                            {{-- <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="chk_entregas" name="entregas"
                                    value="1" @if (!empty($_GET['entregas'])) checked @endif>
                                <label class="custom-control-label" for="chk_entregas">Mostrar entregas realizadas</label>
                            </div> --}}

                            <div class="d-flex align-items-center">
                                <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                                <a href="{{ route('cc_seller', $seller->id) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- DICAS / AÇÕES SECUNDÁRIAS (opcional) --}}
            <div class="col-lg-3 nao-imprimir">
                <div class="card card-lift mb-3">
                    <div class="card-header">
                        <strong>Dicas</strong>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small">
                            Use os filtros por produto e a opção de entregas realizadas para refinar a visualização.
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
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Categoria</th>
                            <th>Produto</th>
                            <th class="text-right">Quant</th>
                            <th class="text-right">Saldo</th>
                            <th>Data Entrega</th>
                            <th>Tipo Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            @php
                                $rowClass =
                                    $item->saldo < 0
                                        ? 'row-positive'
                                        : ($item->saldo > 0 && $item->delivery_date < date('Y-m-d')
                                            ? 'row-late'
                                            : 'row-neutral');
                            @endphp
                            <tr class="linha {{ $rowClass }}" data-id="{{ $item->id }}">
                                <td>{{ date('d/m/Y', strtotime($item->order_date)) }}</td>
                                <td>
                                    <a
                                        href="{{ route('order_products.index', ['order' => $item->order->id]) }}">#{{ $item->order->order_number }}</a>
                                </td>
                                <td>{{ $item->order->client->name ?? ' - ' }}</td>
                                <td>{{ $item->order->client->category->name ?? ' - ' }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td class="text-right">{{ number_format($item->quant, 0, '', '.') }}</td>
                                <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                <td class="d-flex flex-column">{{ date('d/m/Y', strtotime($item->delivery_date)) }}
                                    <span class="badge badge-danger @if (!$item->favorite_delivery) d-none @endif"
                                        style="width: fit-content;">Data
                                        fixada</span>
                                <td>
                                    @php $isCif = ($item->order->withdraw === 'entregar'); @endphp
                                    <span class="badge {{ $isCif ? 'badge-success' : 'badge-info' }}">
                                        {{ $item->order->withdraw }} ({{ $isCif ? 'CIF' : 'FOB' }})
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

        /* destaque Vendedor (amarelo) */
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
            background: #6f42c1;
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
    </script>
@endsection
