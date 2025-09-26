@extends('layouts.estilos')

@section('title', 'Entregas do Pedido')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas do Pedido</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_sair" href="{{ route('orders.index') }}">Voltar</a>
                <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="card card-lift">
            <div class="table-responsive tableFixHead">
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
                        @foreach ($order_products as $item)
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
