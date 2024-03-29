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
                    <button class="btn btn-sm btn-secondary" onclick="javascript:history.go(-1);" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="
                        this.style.display = 'none';
                        document.getElementById('btn_voltar').style.display = 'none';
                        window.print();
                        javascript:history.go(0);
                    ">Imprimir</button>
                    @if ($order->complete_order !== 0)
                        <label for="order_change_status">Alterar Status do Pedido:</label>
                        <input type="hidden" name="order_change_id" id="order_change_id" value="{{$order->id}}">
                        <select name="order_change_status" id="order_change_status">
                            <option @if($order->complete_order === 0) selected @endif value="0">Aberto</option>
                            <option @if($order->complete_order === 1) selected @endif value="1">Entregue</option>
                            <option @if($order->complete_order === 2) selected @endif value="2">Cancelado</option>
                        </select>
                    @endif
                </div>
            </div>
            <div class="col-md m-1 d-flex justify-content-center">
                <label>Falta Entregar:</label>
                <ul>
                    @foreach ($saldo_produtos as $item)
                        <li>{{$item->product_name}} = {{number_format($item->saldo, 0, '', '.')}} <br></li>
                    @endforeach
                </ul>
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
                    <th style="width: 75px;">Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order_products as $item)

                <tr style="text-align: center; @if($item->quant < 0) color:red; @endif"">
                    <td style="padding: 5px;">
                        {{$item->product_name}}
                    </td>
                    <td style="padding: 5px; id="quant_prod">
                        {{number_format($item->quant, 0, '', '.')}}
                    </td>
                    <td style="padding: 5px;">
                        R$ {{number_format($item->unit_price, 2, ',', '.')}}
                    </td>
                    <td style="padding: 5px;">
                        R$ {{number_format($item->total_price, 2, ',', '.')}}
                    </td>
                    <td style="padding: 5px;">
                        @if ($item->quant < 0)
                            {{date('d/m/Y', strtotime($item->created_at))}}
                        @else
                            {{date('d/m/Y', strtotime($item->delivery_date))}}
                        @endif
                    </td>
                </tr>
                    
                @endforeach

            </tbody>
        </table>

    </main>

        <!-- Modal Confirmar Entrega -->
        <div class="modal fade" id="confirmarEntrega" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Registrar Entrega do Produto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Produto:
                    <select name="produto" id="produto">
                        <option>Selecione o Produto:</option>
                        @foreach($product as $key => $item)
                        <option value="{{$key}}">{{$item}}</option>
                        @endforeach
                    </select>
                    Quantidade:
                    <input type="search" name="quant" id="quant">
                    <input type="hidden" id="order_number" value="{{$order->order_number}}">
                    <input type="hidden" id="order_id" value="{{$order->id}}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="deliver">Confirmar</button>
                </div>
                </div>
            </div>
        </div>

        <!-- Modal Cancelar Pedido -->
        <div class="modal fade" id="cancelarPedido" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Cancelar Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                {{-- <div class="modal-body">
                    ...
                </div> --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Desistir</button>
                    <button type="button" class="btn btn-primary" id="cancel" data-id="{{$order->id}}">Confirmar</button>
                </div>
                </div>
            </div>
        </div>
    
@endsection

@section('js')
    <script>

        $(function(){
            $('#quant_prod').mask('000.000.000', {reverse:true});
            $('#quant').mask('000.000.000', {reverse:true});

            $('#produto').change(function(){
                let id_prod = $(this).val();
                let order = $('#order_number').val();
                inserir_quant_produto(id_prod, order);
            });

            function inserir_quant_produto(id, order) {
                $.ajax({
                    url:"{{route('saldo_produto')}}",
                    type:'get',
                    data:{id:id, order:order},
                    dataType:'json',
                    success:function(json){
                        function formatar(nr) {
                        return String(nr)
                            .split('').reverse().join('').split(/(\d{3})/).filter(Boolean)
                            .join('.').split('').reverse().join('');
                        }
                        let formatado = formatar(json['saldo']);
                        $('#quant').val(formatado);
                        $('#quant').attr('max', formatado);
                    }
                });
            }

            $('#order_change_status').change(function(){
                let stat = $(this).val();
                let id = $('#order_change_id').val();
                
                $.ajax({
                    url:"{{route('order_change_status')}}",
                    type:'get',
                    data:{id:id, stat:stat},
                    dataType:'json',
                    success:function(json){}
                });
                
                window.location.reload();
            });

            $('#deliver').click(function(){

                if (confirm('Deseja confirmar a entrega?')) {
                    let id = $('#order_id').val();
                    let order = $('#order_number').val();
                    let id_prod = $('#produto').val();
                    let quant = $('#quant').val();
                    let max = $('#quant').attr('max');
                    let delivered = '';

                    quant = parseInt(quant.replace(/[^\d]+/g,''));
                    max = parseInt(max.replace(/[^\d]+/g,''));
                    
                    if (quant > max) {
                        alert('Você não pode entregar mais que o saldo do pedido');
                        inserir_quant_produto(id_prod, order);
                        return false;
                    } else if (quant == max) {
                        delivered = 'total';
                    } else if (quant < max) {
                        delivered = 'parcial';
                    }
                    
                    $.ajax({
                        url:"{{route('register_delivery')}}",
                        type:'get',
                        data:{id:id, id_prod:id_prod, quant:quant, delivered:delivered},
                        dataType:'json',
                        success:function(json){
                            window.location.href = json;
                        }
                    });

                } else {
                    return false;
                }
            })

            $('#cancel').click(function(){
                if (confirm('Deseja cancelar o pedido?')) {
                    let id = $(this).attr('data-id');
                    $.ajax({
                        url:"{{route('register_cancel')}}",
                        type:'get',
                        data:{id:id},
                        dataType:'json',
                        success:function(json){
                            window.location.href = json;
                        }
                    });    
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