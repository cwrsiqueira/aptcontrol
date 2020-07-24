@extends('layouts.estilos')

@section('title', 'Entregas')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Entregas nesta data</h2>
        <div class="row">
            <div class="card col-md-6 m-3">
            <div class="card-header"><span style="font-size: 24px; font_weight:bold;">{{date('d/m/Y', strtotime($date ?? ''))}}</span></div>
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
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = 'reports'" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = 'reports';">Imprimir</button>
                </div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Produto</th>
                    <th>Quant</th>
                    <th>Endereço</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $item)
                    <tr>
                        <td>{{$item->order_id}}</td>
                        <td>{{$item->client_name}}</td>
                        <td>{{$item->product_name}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{$item->client_address}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
@endsection

