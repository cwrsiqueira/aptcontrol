@extends('layouts.template')

@section('title', 'Pedido')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Editar Pedido nr. {{$order->order_number}}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{route('orders.update', ['order' => $order->id])}}" method="post">
            @csrf
            @method('PUT')
            <table class="table">
                <thead>
                    <tr>
                        <th colspan="1">Data: <input class="form-control" type="date" name="order_date" id="order_date" value="{{$order->order_date}}"><input type="hidden" name="order_id" id="order_id" value="{{$order->id}}"></th>
                        <th colspan="5">Cliente: <input readonly class="form-control" type="text" name="client_name" id="client_name" value="{{$order->name_client}}"></th>
                    </tr>
                    <tr>
                        <th colspan="1">Pedido Nr.: <input readonly class="form-control" type="text" name="order_number" id="order_number" value="{{$order->order_number}}"></th>
                        <th colspan="2">Valor do Pedido: <input class="form-control" readonly type="text" name="total_order" id="total_order" value="{{number_format($order->order_total, 2, ',', '.')}}"></th>
                        <th colspan="1">
                            Pagamento:
                            <select class="form-control" name="payment" id="payment">
                                <option value="Aberto">Aberto</option>
                                <option value="Parcial">Parcial</option>
                                <option value="Total">Total</option>
                            </select>
                        </th>

                        <th colspan="1">
                            Recebimento do Material:
                            <select class="form-control" name="withdraw" id="withdraw">
                                <option value="Entregar">Entregar</option>
                                <option value="Retirar">Retirar</option>
                            </select><small class="order_number_align_size" style="color:transparent;"></small>
                        </th>

                        <th colspan="1" style="text-align: center"><a class="add_line" style='color:green;' href='#' data-toggle='tooltip' title='Adicionar linha!'><i class='fas fa-fw fa-plus' style="font-size: 24px;"></i></a><br><small class="order_number_align_size" style="color:transparent;"></small></th>
                    </tr>
                    <tr style="text-align: center;">
                        <th style="width: 250px;">Produto</th>
                        <th style="width: 75px;">Quant.</th>
                        <th style="width: 125px;">Vlr.Unit.</th>
                        <th style="width: 125px;">Vlr.Total</th>
                        <th style="width: 75px;">Previsão de Entrega</th>
                        <th style="width: 25px;">Linha</th>
                    </tr>
                </thead>
                <tbody class="table-prod">

                    @foreach ($order_products as $item)
                    <tr class="line{{$item->id}}" style="text-align: center;">
                        <td style="padding: 5px;">
                            <select readonly class="form-control product_name{{$item->id}}" style="width: 100%;" name="prod[{{$item->id}}][product_name]">
                                @foreach ($products as $product)
                                    <option @if($item->product_id !== $product->id) style="display:none;" @else selected @endif value="{{$product->id}}">{{$product->name}}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="padding: 5px;">
                            <input readonly class="form-control quant{{$item->id}} qt_mask" style="width: 100%;" type="text" name="prod[{{$item->id}}][quant]" value="{{$item->quant}}">'
                        </td>
                        <td style="padding: 5px;">
                            <input readonly class="form-control unit_val{{$item->id}}" style="width: 100%;" type="text" name="prod[{{$item->id}}][unit_val]" value="{{number_format($item->unit_price, 2, ',', '.')}}">'
                        </td>
                        <td style="padding: 5px;">
                            <input readonly class="form-control total_val{{$item->id}}" style="width: 100%;" type="text" name="prod[{{$item->id}}][total_val]" value="{{number_format($item->total_price, 2, ',', '.')}}" readonly>'
                        </td>
                        <td style="padding: 5px;">
                            <input readonly class="form-control delivery_date{{$item->id}}" style="width: 100%;" type="date" name="prod[{{$item->id}}][delivery_date]" value="{{$item->delivery_date}}">
                        </td>
                        <td style='padding: 5px;'>
                            <a class='remove_line' style='color:red' href='#' data-toggle='tooltip' title='Excluir linha!' data-id="line{{$item->id}}"><i class='fas fa-fw fa-trash'></i></a>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
            <hr>
            <div>
                <input class="btn btn-success" type="submit" value="Salvar">
            </div>
        </form>
        <div>
            <a class="btn btn-danger mt-3" href="{{route('orders.index')}}">Voltar</a>
        </div>
    </main>
    
@endsection

@section('js')
    <script>

        $(function(){
            $('.remove_line').on('click', function(){
                if (confirm('Confirma a exclusão da linha?')) {
                    let line = $(this).attr('data-id');
                    $('.'+line).remove();
                } else {
                    return false;
                }
            });
            $('#quant_prod').mask('000.000.000', {reverse:true});
            // Inclusão de linhas
            $('.add_line').click(function(e){

                e.preventDefault();
                let html = "";
                let r = new Date().getTime();

                if ($('.prod'+r).length == 0) {

                    html += '<tr class="prod'+r+'" style="text-align: center;">';
                    html += '<td style="padding: 5px;">';
                    html += '<select class="form-control product_name'+r+'" style="width: 100%;" name="prod['+r+'][product_name]">'
                    html += '<option value=""></option>';
                    html += '@foreach ($products as $product)';
                    html += '<option value="{{$product->id}}">{{$product->name}}</option>';
                    html += '@endforeach';
                    html += '</select>';
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control quant'+r+' qt_mask" style="width: 100%;" type="text" name="prod['+r+'][quant]" value="0">'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control unit_val'+r+'" style="width: 100%;" type="text" name="prod['+r+'][unit_val]" value="0">'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control total_val'+r+'" style="width: 100%;" type="text" name="prod['+r+'][total_val]" value="0" readonly>'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control delivery_date'+r+'" style="width: 100%;" type="date" name="prod['+r+'][delivery_date]" value="{{date("Y-m-d")}}">';
                    html += '</td>';
                    html += "<td style='padding: 5px;'><a class='new_line"+r+"' style='color:red' href='#' data-toggle='tooltip' title='Excluir linha!' id='"+r+"'><i class='fas fa-fw fa-trash'></i></a></td>";
                    html += '</tr>';
                }

                $('.table-prod').append(html);
                $('[data-toggle="tooltip"]').tooltip({trigger: "hover"});

                $('.unit_val'+r+'').blur(function(){
                    let total = 0;
                    total = $('#total_order').val().replace('.', '').replace(',', '.');
                    let vlr_alterado = $(this).val().replace('.', '').replace(',', '.');
                    let total_val = ($('.quant'+r+'').val().replace('.', '') * vlr_alterado) / 1000;
                    let formatado = total_val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    $('.total_val'+r+'').val(formatado);
                    total = parseFloat(total) + parseFloat(total_val);
                    let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    $('#total_order').val(total_formatado);
                })

                // Calcular Dia de Entrega
                $('.quant'+r+'').blur(function(){
                    let id = $('.product_name'+r+'').val()
                    let quant = $(this).val()
                    if (id === '') {
                        alert('Selecionar o produto');
                        $('.product_name'+r+'').focus();
                    } else {
                        $.ajax({
                            url:"{{route('day_delivery_calc')}}",
                            type:'get',
                            data:{id:id, quant:quant},
                            dataType:'json',
                            success:function(json){
                                $('.delivery_date'+r+'').val(json)
                            },
                        });
                    }
                })

                $('.quant'+r).blur(function(){
                    $(this).attr('readonly', 'readonly');
                })

                $('.unit_val'+r).blur(function(){
                    $(this).attr('readonly', 'readonly');
                })

                // Excluir Linha nova (Não Salva no BD)
                $('.new_line'+r).click(function (e) {
                    e.preventDefault();
                    $('[data-toggle="tooltip"]').tooltip('hide');
                    let linha = $(this).attr('id');
                    let total = $('#total_order').val().replace('.', '').replace(',', '.');
                    let deleted_val = $('.total_val'+r+'').val().replace('.', '').replace(',', '.');
                    total = parseFloat(total) - parseFloat(deleted_val);
                    let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    $('#total_order').val(total_formatado);
                    $('.prod'+linha).remove();
                })

                $('.unit_val'+r+'').mask('000.000,00', {reverse:true});
                $('.qt_mask').mask('000.000.000', {reverse:true}); 
            });
        });
        
    </script>
@endsection