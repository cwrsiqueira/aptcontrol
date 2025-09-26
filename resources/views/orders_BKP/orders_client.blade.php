@extends('layouts.estilos')

@section('title', 'C/C Cliente')

@section('content')

<table class="table">
    <thead>
        <tr>
            <th>Pedido</th>
            <th>Produto</th>
            <th>Quantidade</th>
            <th>Data</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($orders as $item)
        <tr>
            <td>{{$item->order_id}}</td>
            <td>{{$item->product_id}}</td>
            <td>{{$item->quant}}</td>
            <td>
                @if($item->delivery_date == '0000-00-00' || $item->delivery_date == '1970-01-01')
                    {{date('d-m-Y', strtotime($item->updated_at))}}
                    @else
                    {{date('d-m-Y', strtotime($item->delivery_date))}}
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection