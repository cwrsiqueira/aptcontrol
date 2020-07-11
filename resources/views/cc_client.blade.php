@extends('layouts.estilos')

@section('title', 'C/C Cliente')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Cliente</h2>
        <div class="row">
            <div class="card col-md-6 m-3">
                <div class="card-header"><span>{{$client->name}}</span></div>
                <div class="card-body">
                    <ul>
                        @foreach ($product_total as $key => $value)
                            <li>Total de {{$key}}: {{number_format($value, 0, '', '.')}}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../../clients'" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = '../../clients';">Imprimir</button>
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
                    <tr>
                        <td>{{date('d/m/Y', strtotime($item->order_date))}}</td>
                        <td>{{$item->order_id}}</td>
                        <td>{{$item->product_name}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{date('d/m/Y', strtotime($item->delivery_date))}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{$data->links()}}
    </main>
@endsection

