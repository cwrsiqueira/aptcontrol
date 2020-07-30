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
                @if (!empty($date))
                {{date('d/m/Y', strtotime($date ?? $date_ini.'/'.$date_fin))}}
                @else
                {{date('d/m/Y', strtotime($date_ini))}} a {{date('d/m/Y', strtotime($date_fin))}}
                @endif
                </span></div>

                @if (!empty($date))
                <form action="{{route('report_delivery')}}" method="get">
                @else
                <form action="{{route('report_delivery_byPeriod')}}" method="get">
                @endif

                    <div class="card-body">
                        <ul>
                            @foreach ($product_total as $key => $value)
                                <input class="mr-1" type="checkbox" name="por_produto[]"  value="{{$value['id']}}" @if(!empty($_GET['por_produto']) && in_array($value['id'], $_GET['por_produto'])) checked @endif>Total de {{$key}}: {{number_format($value['qt'], 0, '', '.')}} <br>
                            @endforeach
                        </ul>

                        @if (!empty($date))
                        <input type="hidden" name="delivery_date" value="{{$date}}">
                        @else
                        <input type="hidden" name="date_ini" value="{{$date_ini}}">
                        <input type="hidden" name="date_fin" value="{{$date_fin}}">
                        @endif

                        @if (!empty($date))
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{route('report_delivery', ['delivery_date' => $date])}}" id="clean_search">Limpar Filtro</a>
                        @else
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{route('report_delivery_byPeriod', ['date_ini' => $date_ini, 'date_fin' => $date_fin])}}" id="clean_search">Limpar Filtro</a>
                        @endif

                    </div>
                </form>
                {{-- <div class="card-body">
                    <ul>
                        @foreach ($product_total as $key => $value)
                            <li>Total de {{$key}}: {{number_format($value, 0, '', '.')}}</li>
                        @endforeach
                    </ul>
                </div> --}}
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = 'reports'" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="
                        this.style.display = 'none';
                        document.getElementById('btn_voltar').style.display = 'none';
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
                    <th>Produto</th>
                    <th>Quant</th>
                    <th>Contato</th>
                    <th>Endereço</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $item)
                    <tr @if($item->delivery_date < date('Y-m-d')) style="color:red;font-weight:bold;" @endif >
                        <td>{{$item->order_id}}</td>
                        <td>{{date('d-m-Y', strtotime($item->delivery_date))}}</td>
                        <td>{{$item->client_name}}</td>
                        <td>{{$item->product_name}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{$item->client_phone}}</td>
                        <td style="width:200px">{{$item->client_address}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
@endsection

