@extends('layouts.estilos')

@section('title', 'C/C Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Produto</h2>
        <div class="row">
            <div class="card col-md-6 m-3">
                <div class="card-header"><span>{{$product->name}}</span></div>
                <div class="card-body">
                    Total a entregar: {{number_format($quant_total ?? 0, 0, '', '.') ?? 0}} <br>
                    Entregas a partir de: {{date('d/m/Y', strtotime($delivery_in))}} <br>
                </div>
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../../products'" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = '../../products';">Imprimir</button>
                </div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Quant</th>
                    <th>Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr>
                        <td>{{date('d/m/Y', strtotime($item->order_date))}}</td>
                        <td>{{$item->client_name}}</td>
                        <td>{{$item->order_id}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{date('d/m/Y', strtotime($item->delivery_date))}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{$data->links()}}
    </main>
@endsection

