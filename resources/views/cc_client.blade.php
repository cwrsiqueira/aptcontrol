@extends('layouts.estilos')

@section('title', 'C/C Cliente')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Cliente</h2>
        <div class="row">
            <div class="card col-md-6 m-3">
                <div class="card-header"><span>{{$client->name}}</span></div>
                <form action="{{route('cc_client', ['id' => $client->id])}}" method="get">
                    <div class="card-body">
                        <ul>
                            @foreach ($product_total as $key => $value)
                                <input class="mr-1" type="checkbox" name="por_produto[]"  value="{{$value['id']}}" @if(!empty($_GET['por_produto']) && in_array($value['id'], $_GET['por_produto'])) checked @endif>Total de {{$key}}: {{number_format($value['qt'], 0, '', '.')}} <br>
                            @endforeach
                        </ul>
                        <label for="">De:</label>
                        <input type="date" name="date_ini" value="{{date('Y-m-d', strtotime($_GET['date_ini'] ?? date('Y-m-01')))}}">
                        <label for="">Até:</label>
                        <input type="date" name="date_fin" value="{{date('Y-m-d', strtotime($_GET['date_fin'] ?? date('Y-m-t')))}}">
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{route('cc_client', ['id' => $client->id])}}" id="clean_search">Limpar Filtro</a>
                    </div>
                </form>
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="javascript:history.go(-1);" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="
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
                    <th>Data</th>
                    <th>Pedido</th>
                    <th>Produto</th>
                    <th>Quant</th>
                    <th>Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr @if($item->delivery_date < date('Y-m-d')) style="color:red;font-weight:bold;" @endif >
                        <td>{{date('d/m/Y', strtotime($item->order_date))}}</td>
                        <td>{{$item->order_id}}</td>
                        <td>{{$item->product_name}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{date('d/m/Y', strtotime($item->delivery_date))}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- {{$data->links()}} --}}
    </main>
@endsection

