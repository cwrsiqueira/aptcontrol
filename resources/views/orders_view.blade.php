@extends('layouts.estilos')

@section('title', 'Pedido')

@section('content')
    <main>
        @if ($order->complete_order === 1)
            <div class="delivered">
                ENTREGUE
            </div>
        @endif
        @if ($order->complete_order === 2)
            <div class="canceled">
                CANCELADO
            </div>
        @endif
    </main>
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedido nr. {{$order->order_number}}</h2>

        <div class="row">
            <div class="col-md m-3">
                <div class="card-tools">
                    @if ($order->complete_order === 1 || $order->complete_order === 2)
                        <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../orders?comp=1'" id="btn_voltar">Voltar</button>
                    @else
                        <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../orders'" id="btn_voltar">Voltar</button>
                    @endif
                    <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = '../orders';">Imprimir</button>
                </div>
            </div>
            @if ($order->complete_order !== 1 && $order->complete_order !== 2)
                <div class="col-md m-3 d-flex justify-content-end">
                    <div class="card-tools">
                        <button style="width:135px;" class="btn btn-sm btn-secondary btn-success" id="deliver" data-id="{{$order->id}}">Confirmar Entrega</button>
                        <button style="width:135px" class="btn btn-sm btn-secondary btn-danger" id="cancel" data-id="{{$order->id}}">Cancelar Pedido</button>
                    </div>
                </div>
            @endif
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
                        <label for="payment">Pagamento:</label><br>
                        {{$order->payment}}
                    </th>
                    <th>
                        <label for="withdraw">Entrega:</label><br>
                        {{$order->withdraw}}
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
                        {{number_format($item->quant, 0, '', '.')}}
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
            $('#quant_prod').mask('000.000.000', {reverse:true});

            $('#deliver').click(function(){
                if (confirm('Deseja confirmar a entrega?')) {
                    let id = $(this).attr('data-id')
                    $.ajax({
                        url:"{{route('register_delivery')}}",
                        type:'get',
                        data:{id:id},
                        dataType:'json',
                        success:function(json){
                            window.location.href = json;
                        }
                    })    
                } else {
                    return false;
                }
            })
            $('#cancel').click(function(){
                if (confirm('Deseja cancelar o pedido?')) {
                    let id = $(this).attr('data-id')
                    $.ajax({
                        url:"{{route('register_cancel')}}",
                        type:'get',
                        data:{id:id},
                        dataType:'json',
                        success:function(json){
                            window.location.href = json;
                        }
                    })    
                } else {
                    return false;
                }
            })
        })
        
    </script>
@endsection

@section('css')
<style>
    .delivered {
        position: absolute;
        top: 50%;
        width: 100%;
        text-align: center;
        font-size: 18px;
        font-size: 150px; 
        font-weight: bold; 
        color: rgba(110, 168, 108, 0.6); 
        transform: rotate(-45deg);
        z-index: 9;
    }
    .canceled {
        position: absolute;
        top: 50%;
        width: 100%;
        text-align: center;
        font-size: 18px;
        font-size: 150px; 
        font-weight: bold; 
        color: rgba(165, 74, 74, 0.6); 
        transform: rotate(-45deg);
        z-index: 9;
    }
</style>
    
@endsection