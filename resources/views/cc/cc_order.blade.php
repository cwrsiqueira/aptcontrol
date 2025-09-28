@extends('layouts.estilos')

@section('title', 'Entregas por Pedido')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas por Pedido</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_sair" href="{{ route('orders.index') }}">
                    < Pedidos</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- CLIENTE --}}
            <div class="col-lg-4">
                <div class="card card-lift mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <strong>Pedido</strong>
                        <span class="badge badge-primary badge-client-name">
                            <a class="text-light"
                                href="{{ route('order_products.index', ['order' => $order->id]) }}">#{{ $order->order_number }}</a>
                        </span>
                    </div>
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span class="badge badge-info badge-client-name">Cliente: {{ $client->name }}</span>
                        <span class="font-weight-bold">{{ $client->category->name }}</span>
                    </div>
                    <div class="card-body">
                        @if ($client->is_favorite)
                            <div class="d-block mb-3">
                                <div class="fav-client is-fav-client d-inline-block">Cliente aguardando antecipação</div>
                            </div>
                        @endif
                        <small class="text-muted nao-imprimir">Selecione filtros ao lado e veja as entregas abaixo.</small>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg-5">
                <form action="{{ route('cc_order', ['id' => $order->id]) }}" method="get">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros por produto</strong>
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

                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="chk_entregas" name="entregas"
                                    value="1" @if (!empty($_GET['entregas'])) checked @endif>
                                <label class="custom-control-label" for="chk_entregas">Mostrar entregas realizadas</label>
                            </div>

                            <div class="d-flex align-items-center">
                                <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                                <a href="{{ route('cc_order', ['id' => $order->id]) }}" id="clean_search"
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
                            <th>Produto</th>
                            <th class="text-right">Quant</th>
                            <th class="text-right">Saldo</th>
                            <th>Vendedor</th>
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
                                <td>{{ $item->product->name }}</td>
                                <td class="text-right">{{ number_format($item->quant, 0, '', '.') }}</td>
                                <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                <td>{{ $item->order->seller->name ?? ' - ' }}</td>
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

        /* hover suave */
        tbody tr:hover {
            background-color: #f6f9fc;
            cursor: pointer;
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

        /* header */
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
        $(function() {
            // reload uma vez (mantendo seu comportamento)
            if (window.localStorage) {
                if (!localStorage.getItem('firstLoad')) {
                    localStorage['firstLoad'] = true;
                    window.location.reload();
                } else {
                    localStorage.removeItem('firstLoad');
                    $('html,body').scrollTop(0);
                }
            }

            // imprimir
            $('#btn_imprimir').click(function() {
                $(this).hide();
                $('#btn_voltar').hide();
                $('#btn_sair').hide();
                $('#search').hide();
                $('#clean_search').hide();
                $('.btn_acoes').hide();
                $('.nao-imprimir').hide();
                window.print();
                history.go(0);
            });
        });
    </script>
@endsection
