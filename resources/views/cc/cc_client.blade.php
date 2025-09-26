@extends('layouts.estilos')

@section('title', 'Entregas do Cliente')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas do Cliente</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_sair" href="{{ route('clients.index') }}">Voltar</a>
                <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- CLIENTE --}}
            <div class="col-lg-4">
                <div class="card card-lift mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <strong>Cliente</strong>
                        <span class="badge badge-primary badge-client-name">{{ $client->name }}</span>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">Selecione filtros ao lado e veja as entregas abaixo.</small>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg-5">
                <form action="{{ route('cc_client', ['id' => $client->id]) }}" method="get">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros</strong>
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
                                <a href="{{ route('cc_client', ['id' => $client->id]) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- DICAS / AÇÕES SECUNDÁRIAS (opcional) --}}
            <div class="col-lg-3">
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
                            <th>Produto</th>
                            <th class="text-right">Quant</th>
                            <th class="text-right">Saldo</th>
                            <th>Entrega</th>
                            <th class="btn_acoes" colspan="2">Ações</th>
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
                                <td>#{{ $item->order_id }}</td>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-right">{{ number_format($item->quant, 0, '', '.') }}</td>
                                <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                <td>
                                    @if ($item->quant < 0)
                                        {{ date('d/m/Y', strtotime($item->created_at)) }}
                                    @else
                                        {{ date('d/m/Y', strtotime($item->delivery_date)) }}
                                    @endif
                                </td>
                                <td class="btn_acoes">
                                    @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-secondary"
                                            href="{{ route('orders.edit', ['order' => $item->orders_order_id]) }}">Editar</a>
                                    @else
                                        <button class="btn btn-sm btn-secondary" disabled
                                            title="Solicitar Acesso">Editar</button>
                                    @endif
                                </td>
                                <td class="btn_acoes">
                                    @if (in_array('orders.conclude', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-secondary"
                                            href="{{ route('orders_conclude', ['order' => $item->orders_order_id]) }}">Concluir</a>
                                    @else
                                        <button class="btn btn-sm btn-secondary" disabled
                                            title="Solicitar Acesso">Concluir</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- <div class="card-footer">{{ $data->links() }}</div> --}}
        </div>
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

        /* coloração das linhas de acordo com regra existente */
        .row-positive {
            color: green;
            font-weight: 700;
        }

        .row-late {
            color: #d9534f;
            font-weight: 700;
        }

        /* vermelho */
        .row-neutral {
            color: #777;
            font-weight: 700;
        }

        /* hover suave */
        tbody tr:hover {
            background-color: #f6f9fc;
            cursor: pointer;
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
                window.print();
                history.go(0);
            });
        });
    </script>
@endsection
