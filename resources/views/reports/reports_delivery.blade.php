@extends('layouts.estilos')

@section('title', 'Entregas')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        @if (!empty($date))
            <h2>Entregas até esta data</h2>
        @else
            <h2>Entregas neste período</h2>
        @endif

        <div class="row">
            <div class="card col-md-6 m-3">
                <div class="card-header"><span style="font-size: 24px; font_weight:bold;">
                        <i class='fas fa-arrow-left less-date' style='font-size:24px;'></i>
                        @if (!empty($date))
                            {{ date('d/m/Y', strtotime($date ?? $date_ini . '/' . $date_fin)) }}
                        @else
                            {{ date('d/m/Y', strtotime($date_ini)) }} a {{ date('d/m/Y', strtotime($date_fin)) }}
                        @endif
                        <i class='fas fa-arrow-right plus-date' style='font-size:24px;'></i>
                    </span></div>

                @if (!empty($date))
                    <form action="{{ route('report_delivery') }}" method="get">
                    @else
                        <form action="{{ route('report_delivery_byPeriod') }}" method="get">
                @endif

                <div class="card-body">

                    <label>Filtrar por Tipo de Material:</label>
                    <ul>
                        @foreach ($product_total as $key => $value)
                            <input class="mr-1" type="checkbox" name="por_produto[]" value="{{ $value['id'] }}"
                                @if (!empty($_GET['por_produto']) && in_array($value['id'], $_GET['por_produto'])) checked @endif>Total de {{ $key }}:
                            {{ number_format($value['qt'], 0, '', '.') }} <br>
                        @endforeach
                    </ul>

                    <label for="withdraw">Filtrar por Forma de Entrega:</label>
                    <select class="form-control col-sm-4 mb-3 ml-3" name="withdraw" id="withdraw">
                        <option></option>
                        <option @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Entregar') selected @endif value="Entregar">Entregar na Obra</option>
                        <option @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Retirar') selected @endif value="Retirar">Retirar na Fábrica
                        </option>
                    </select>

                    @if (!empty($date))
                        <input type="hidden" name="delivery_date" value="{{ $date }}">
                    @else
                        <input type="hidden" name="date_ini" value="{{ $date_ini }}">
                        <input type="hidden" name="date_fin" value="{{ $date_fin }}">
                    @endif

                    @if (!empty($date))
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{ route('report_delivery', ['delivery_date' => $date]) }}" id="clean_search">Limpar
                            Filtro</a>
                    @else
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{ route('report_delivery_byPeriod', ['date_ini' => $date_ini, 'date_fin' => $date_fin]) }}"
                            id="clean_search">Limpar Filtro</a>
                    @endif
                </div>
                </form>
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = 'reports'"
                        id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary"
                        onclick="
                        this.style.display = 'none';
                        document.getElementById('btn_voltar').style.display = 'none';
                        document.getElementById('search').style.display = 'none';
                        document.getElementById('clean_search').style.display = 'none';
                        window.print();
                        javascript:history.go(0);
                    ">Imprimir</button>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Entrega</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Produto</th>
                    <th>Saldo</th>
                    <th>Contato</th>
                    @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Retirar')
                        <th>Forma de Entrega</th>
                    @else
                        <th>Forma de Entrega</th>
                        <th>Endereço</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $item)
                    <tr @if ($item->delivery_date < date('Y-m-d')) style="color:red;font-weight:bold;" @endif>
                        <td>{{ $item->order_id }}</td>
                        <td style="min-width:100px">{{ date('d-m-Y', strtotime($item->delivery_date)) }}</td>
                        <td>{{ $item->client_name }}</td>
                        <td>{{ $item->seller_name }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ number_format($item->saldo, 0, '', '.') }}</td>
                        <td>{{ $item->client_phone }}</td>
                        @if (!empty($_GET['withdraw']) && $_GET['withdraw'] == 'Retirar')
                            <td>{{ $item->withdraw }}</td>
                        @else
                            <td>{{ $item->withdraw }}</td>
                            <td>{{ $item->client_address }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
@endsection

@section('css')
    <style>
        .plus-date,
        .less-date {
            cursor: pointer;
        }

        .plus-date,
        .less-date {
            color: gray;
        }

        .plus-date:hover,
        .less-date:hover {
            color: black;
        }
    </style>
@endsection

@section('js')
    <script>
        $(function() {
            $('.plus-date').click(function() {
                let date = "{{ date('Y-m-d', strtotime($_GET['delivery_date'] . ' + 1 days')) }}";
                window.location.href = 'report_delivery?delivery_date=' + date;
            })
            $('.less-date').click(function() {
                let date = "{{ date('Y-m-d', strtotime($_GET['delivery_date'] . ' - 1 days')) }}";
                window.location.href = 'report_delivery?delivery_date=' + date;
            })
        })
    </script>
@endsection
