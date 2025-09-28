@extends('layouts.estilos')

@section('title', 'Entregas')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">

        {{-- Cabeçalho --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">
                Entregas no período
            </h2>
            <div class="btn-group">
                <button class="btn btn-sm btn-secondary" onclick="window.location.href = 'reports'" id="btn_voltar">
                    < Relatórios</button>
                        <button class="btn btn-sm btn-secondary"
                            onclick="
                    this.style.display='none';
                    document.getElementById('btn_voltar').style.display='none';
                    document.getElementById('search').style.display='none';
                    document.getElementById('clean_search').style.display='none';
                    window.print(); history.go(0);">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- CARTÃO DA DATA / NAVEGAÇÃO --}}
            <div class="col-lg-6">
                <div class="card card-lift mb-3">
                    <div class="card-header">
                        <h4 class="mb-0 d-flex align-items-center">
                            <span class="flex-grow-1 text-center" style="letter-spacing:.2px">
                                {{ date('d/m/Y', strtotime($date_ini)) }} a {{ date('d/m/Y', strtotime($date_fin)) }}
                            </span>
                        </h4>
                    </div>

                    @if (!empty($date))
                        <form action="{{ route('report_delivery') }}" method="get">
                        @else
                            <form action="{{ route('report_delivery_byPeriod') }}" method="get">
                    @endif

                    <div class="card-body">
                        {{-- FILTROS --}}
                        <div class="form-group mb-2">
                            <label class="mb-1">Filtrar por Tipo de Material:</label>
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
                        </div>

                        <div class="form-group">
                            <label for="withdraw" class="mb-1">Filtrar por Forma de Entrega:</label>
                            <select class="form-control col-sm-6" name="withdraw" id="withdraw">
                                <option>Todas</option>
                                <option value="Entregar" @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Entregar') selected @endif>
                                    Entregar na Obra
                                </option>
                                <option value="Retirar" @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Retirar') selected @endif>
                                    Retirar na Fábrica
                                </option>
                            </select>
                        </div>

                        {{-- Hidden de data(s) --}}
                        @if (!empty($date))
                            <input type="hidden" name="delivery_date" value="{{ $date }}">
                        @else
                            <input type="hidden" name="date_ini" value="{{ $date_ini }}">
                            <input type="hidden" name="date_fin" value="{{ $date_fin }}">
                        @endif

                        <div class="d-flex align-items-center mt-3">
                            <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                            @if (!empty($date))
                                <a href="{{ route('report_delivery', ['delivery_date' => $date]) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            @else
                                <a href="{{ route('report_delivery_byPeriod', ['date_ini' => $date_ini, 'date_fin' => $date_fin]) }}"
                                    id="clean_search" class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUNA AUXILIAR (vazia para equilíbrio visual em telas largas) --}}
            <div class="col-lg-3 d-none d-lg-block"></div>
        </div>

        {{-- TABELA --}}
        <div class="card card-lift mb-5">
            <div class="table-responsive">
                <table class="table table table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            <th>Data pedido</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Categoria</th>
                            <th>Produto</th>
                            <th class="text-right">Quantidade</th>
                            <th>Data entrega</th>
                            <th>Vendedor</th>
                            <th>Tipo de entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $item)
                            @php $isLate = ($item->delivery_date < date('Y-m-d')); @endphp
                            <tr class="{{ $isLate ? 'row-late' : '' }}">
                                <td>{{ date('d/m/Y', strtotime($item->order->order_date)) }}</td>
                                <td>
                                    <a
                                        href="{{ route('order_products.index', ['order' => $item->order->id]) }}">#{{ $item->order_id }}</a>
                                </td>
                                <td>{{ $item->order->client->name }}</td>
                                <td>{{ $item->order->client->contact }}</td>
                                <td>{{ $item->order->client->category->name }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                <td style="min-width:100px">{{ date('d/m/Y', strtotime($item->delivery_date)) }}</td>
                                <td>{{ $item->order->seller->name }}</td>
                                @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Retirar')
                                    <td>{{ ucfirst($item->order->withdraw) }} (FOB)</td>
                                @else
                                    <td>{{ ucfirst($item->order->withdraw) }} (CIF)</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- <div class="card-footer">{{ $orders->links() }}</div> --}}
        </div>

        <hr>

    </main>
@endsection

@section('css')
    <style>
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .page-header h2 {
            font-weight: 600;
        }

        /* Tabela com cabeçalho fixo e hover suave */
        .tableFixHead {
            max-height: 60vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        tbody tr:hover {
            background-color: #f6f9fc;
        }

        /* Atraso (vermelho) */
        .row-late {
            color: #d9534f;
            font-weight: 700;
        }

        /* Setas de navegação de data */
        .plus-date,
        .less-date {
            cursor: pointer;
            color: gray;
        }

        .plus-date:hover,
        .less-date:hover {
            color: #000;
        }
    </style>
@endsection

@section('js')
    <script>
        $(function() {
            // Navegação de data diária (somente faz sentido quando há $date único)
            $('.plus-date').click(function() {
                @if (!empty($date))
                    const d = new Date("{{ $date }}");
                    d.setDate(d.getDate() + 1);
                    const next = d.toISOString().slice(0, 10);
                    window.location.href = 'report_delivery?delivery_date=' + next;
                @endif
            });
            $('.less-date').click(function() {
                @if (!empty($date))
                    const d = new Date("{{ $date }}");
                    d.setDate(d.getDate() - 1);
                    const prev = d.toISOString().slice(0, 10);
                    window.location.href = 'report_delivery?delivery_date=' + prev;
                @endif
            });
        });
    </script>
@endsection
