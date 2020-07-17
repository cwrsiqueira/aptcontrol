@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4" style="height: 100vh;">
        <h2>Adicionar Pedido</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{route('orders.store')}}" method="post">
            @csrf
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="1">Data: <input class="form-control" type="date" name="order_date" id="order_date" value="{{old('order_date') ?? date('Y-m-d')}}" max="{{date('Y-m-d')}}"></th>
                            <th colspan="5">Cliente: <input readonly class="form-control" type="text" name="client_name" id="client_name" value="{{$client->name}}"><input type="hidden" name="client_id" id="client_id" value="{{$client->id}}"></th>
                        </tr>
                        <tr>
                            <th colspan="1">Pedido Nr.: <input class="form-control @error('order_number') is-invalid @enderror order_number" type="search" name="order_number" value="{{old('order_number') ?? $seq_order_number}}" placeholder="Digite o número do pedido"><small class="order_number_warning" style="color:red;"></small></th>

                            <th colspan="2">Valor do Pedido: <input class="form-control" readonly type="text" name="total_order" id="total_order" value="0"><small class="order_number_align_size" style="color:transparent;"></small></th>

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

                            <th colspan="1" style="text-align: center"><a class="add_line" style='color:green;; display:none;' href='#' data-toggle='tooltip' title='Adicionar linha!'><i class='fas fa-fw fa-plus' style="font-size: 24px;"></i></a><br><small class="order_number_align_size" style="color:transparent;"></small></th>

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
                    <tbody class="table-prod"></tbody>
                </table>
                <hr>
                <div>
                    <input class="btn btn-success" type="submit" value="Salvar">
                </div>
        </form>
    </main>

    @section('js')
        <script>

            $(function(){

                $('.order_number').blur(function(){
                    if ($(this).val() !== '') {
                        $('.add_line').show();
                    }
                    let order_number = $(this).val();
                    $.ajax({
                        url:'{{route("search_order_number")}}',
                        type:'get',
                        data:{data:order_number},
                        dataType:'json',
                        success:function(json){
                            $('.order_number').val(json);
                            if (json != order_number) {
                                $('.order_number_warning').html('Número já utilizado. Adicionado à sequência');
                                $('.order_number_align_size').html('.');
                            }
                        }
                    })
                })

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

@endsection

