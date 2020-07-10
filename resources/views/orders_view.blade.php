@extends('layouts.estilos')

@section('title', 'Pedido')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedido nr. {{$order->order_number}}</h2>
        <div class="col-md-3 m-3">
            <div class="card-tools">
                <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../orders'" id="btn_voltar">Voltar</button>
                <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = '../orders';">Imprimir</button>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th colspan="1">Data: <input readonly class="form-control" type="date" name="order_date" id="order_date" value="{{$order->order_date}}"><input type="hidden" name="order_id" id="order_id" value="{{$order->id}}"></th>
                    <th colspan="4">Cliente: <input readonly class="form-control" type="text" name="client_name" id="client_name" value="{{$order->name_client}}"></th>
                </tr>
                <tr>
                    <th colspan="1">Pedido Nr.: <input readonly class="form-control" type="text" name="order_number" id="order_number" value="{{$order->order_number}}"></th>
                    <th colspan="2">Valor do Pedido: <input class="form-control" readonly type="text" name="total_order" id="total_order" value="R$ {{number_format($order->order_total, 2, ',', '.')}}"></th>
                    <th>
                        <label for="payment">Pagamento:</label>
                        <form id="form_payment" method="post">
                            <select class="form-control" name="payment" id="payment">
                                <option @if ($order->payment === 'Total') selected @endif value="Total">Total</option>
                                <option @if ($order->payment === 'Parcial') selected @endif value="Parcial">Parcial</option>
                                <option @if ($order->payment === 'Aberto') selected @endif value="Aberto">Aberto</option>
                            </select>
                    </th>
                    <th>
                        <label for="withdraw">Entrega:</label>
                            <select class="form-control" name="withdraw" id="withdraw">
                                <option @if ($order->withdraw === 'Entregar') selected @endif value="Entregar">Entregar</option>
                                <option @if ($order->withdraw === 'Retirar') selected @endif value="Retirar">Retirar</option>
                            </select>
                        </form>
                    </th>
                </tr>
                <tr style="text-align: center;">
                    <th style="width: 250px;">Produto</th>
                    <th style="width: 75px;">Quant.</th>
                    <th style="width: 125px;">Vlr.Unit.</th>
                    <th style="width: 125px;">Vlr.Total</th>
                    <th style="width: 75px;">Previsão de Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order_products as $item)

                <tr style="text-align: center;">
                    <td style="padding: 5px;">
                        {{$item->product_name}}
                    </td>
                    <td style="padding: 5px;" id="quant_prod">
                        {{$item->quant}}
                    </td>
                    <td style="padding: 5px;">
                        R$ {{number_format($item->unit_price, 2, ',', '.')}}
                    </td>
                    <td style="padding: 5px;">
                        R$ {{number_format($item->total_price, 2, ',', '.')}}
                    </td>
                    <td style="padding: 5px;">
                        {{date('d/m/Y', strtotime($item->delivery_date))}}
                    </td>
                </tr>
                    
                @endforeach

            </tbody>
        </table>

    </main>
    
@endsection

@section('js')
    <script>

        $(function(){
            $('#payment').change(function(){
                let payment = $(this).val();
                let id = $('#order_id').val();
                
                $.ajax({
                    url:"{{route('edit_payment')}}",
                    type:'get',
                    data:{payment:payment, id:id},
                    dataType:'json',
                    success:function(json){
                        alert(json);
                    },
                });
            })
            $('#withdraw').change(function(){
                let withdraw = $(this).val();
                let id = $('#order_id').val();
                
                $.ajax({
                    url:"{{route('edit_withdraw')}}",
                    type:'get',
                    data:{withdraw:withdraw, id:id},
                    dataType:'json',
                    success:function(json){
                        alert(json);
                    },
                });
            })
            $('#quant_prod').mask('000.000.000', {reverse:true});
        })
        
    </script>
@endsection