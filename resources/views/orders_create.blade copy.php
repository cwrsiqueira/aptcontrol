@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
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
                            <th colspan="1">Data: <input class="form-control" type="date" name="order_date" id="order_date" value="{{date('Y-m-d')}}"></th>
                            <th colspan="4">Cliente: <input readonly class="form-control" type="text" name="client_name" id="client_name" value="{{$client->name}}"><input type="hidden" name="client_id" id="client_id" value="{{$client->id}}"></th>
                        </tr>
                        <tr>
                            <th colspan="1">Pedido Nr.: <input class="form-control @error('order_number') is-invalid @enderror" type="text" name="order_number" id="order_number"></th>
                            <th colspan="3">Valor do Pedido: <input class="form-control" readonly type="text" name="total_order" id="total_order"></th>
                            <th colspan="1">
                                Recebimento do Material:
                                <select class="form-control" name="withdraw" id="withdraw">
                                    <option value="Entregar">Entregar</option>
                                    <option value="Retirar">Retirar</option>
                                </select>
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
                        {{-- LINHA 1 --}}
                        <tr style="text-align: center;">
                            <td style="padding: 5px;">
                                <select class="form-control product_name1" style="width: 100%;" name="product_name1">
                                    <option value=""></option>
                                    @foreach ($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control quant1 qt_mask" style="width: 100%;" type="text" name="quant1">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control unit_val1" style="width: 100%;" type="text" name="unit_val1" value="0">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control total_val1" style="width: 100%;" type="text" name="total_val1" readonly>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control delivery_date1" style="width: 100%;" type="date" name="delivery_date1">
                            </td>
                        </tr>
                        {{--  --}}
                        {{-- LINHA 2 --}}
                        <tr style="text-align: center;">
                            <td style="padding: 5px;">
                                <select class="form-control product_name2" style="width: 100%;" name="product_name2">
                                    <option value=""></option>
                                    @foreach ($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control quant2 qt_mask" style="width: 100%;" type="text" name="quant2">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control unit_val2" style="width: 100%;" type="text" name="unit_val2" value="0">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control total_val2" style="width: 100%;" type="text" name="total_val2" readonly>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control delivery_date2" style="width: 100%;" type="date" name="delivery_date2">
                            </td>
                        </tr>
                        {{--  --}}
                        {{-- LINHA 3 --}}
                        <tr style="text-align: center;">
                            <td style="padding: 5px;">
                                <select class="form-control product_name3" style="width: 100%;" name="product_name3">
                                    <option value=""></option>
                                    @foreach ($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control quant3 qt_mask" style="width: 100%;" type="text" name="quant3">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control unit_val3" style="width: 100%;" type="text" name="unit_val3" value="0">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control total_val3" style="width: 100%;" type="text" name="total_val3" readonly>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control delivery_date3" style="width: 100%;" type="date" name="delivery_date3">
                            </td>
                        </tr>
                        {{--  --}}
                        {{-- LINHA 4 --}}
                        <tr style="text-align: center;">
                            <td style="padding: 5px;">
                                <select class="form-control product_name4" style="width: 100%;" name="product_name4">
                                    <option value=""></option>
                                    @foreach ($products as $product)
                                        <option value="{{$product->id}}">{{$product->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control quant4 qt_mask" style="width: 100%;" type="text" name="quant4">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control unit_val4" style="width: 100%;" type="text" name="unit_val4" value="0">
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control total_val4" style="width: 100%;" type="text" name="total_val4" readonly>
                            </td>
                            <td style="padding: 5px;">
                                <input class="form-control delivery_date4" style="width: 100%;" type="date" name="delivery_date4">
                            </td>
                        </tr>
                        {{--  --}}
                    </tbody>
                </table>
                <hr>
            <input class="btn btn-success mt-3" type="submit" value="Salvar">
        </form>
    </main>

    @section('js')
        <script>

            // Inclusão de linhas nas Fichas do Formulário
            $('#addNosoc-button').on('click', function(e){
                e.preventDefault();
                let html = "";
                //let r = Math.floor(Math.random() * 999) + 1;
                let r = new Date().getTime();
                
                if ($('.nosoc'+r).length == 0) {

                    html += '<tr style="text-align: center;">';
                    html += '<td style="padding: 5px;">';
                    html += '<select class="form-control product_name1" style="width: 100%;" ;name="product_name1">'
                    html += '<option value=""></option>';
                    html += '@foreach ($products as $product)';
                    html += '<option value="{{$product->id}}">{{$product->name}}</option>';
                    html += '@endforeach';
                    html += '</select>';
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control quant1 qt_mask" style="width: 100%;" type="text" ;name="quant1">'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control unit_val1" style="width: 100%;" type="text" ;name="unit_val1" value="0">'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control total_val1" style="width: 100%;" type="text" ;name="total_val1" readonly>'
                    html += '</td>';
                    html += '<td style="padding: 5px;">';
                    html += '<input class="form-control delivery_date1" style="width: 100%;" type="date" ;name="delivery_date1">'
                    html += '</td>';
                    html += '</tr>';
                }
                $('#table-nosoc').append(html);
                $('[data-toggle="tooltip"]').tooltip({trigger: "hover"});
                // Excluir Linha nova (Não Salva no BD)
                $('.new_line').click(function (e) {
                    e.preventDefault();
                    $('[data-toggle="tooltip"]').tooltip('hide');
                    let linha = $(this).attr('id');
                    $('.nosoc'+linha).remove();
                })
            });



            var total = 0;

            $('.unit_val1').mask('000.000,00', {reverse:true});
            $('.unit_val2').mask('000.000,00', {reverse:true});
            $('.unit_val3').mask('000.000,00', {reverse:true});
            $('.unit_val4').mask('000.000,00', {reverse:true});
            $('.qt_mask').mask('000.000.000', {reverse:true});

            $('.unit_val1').blur(function(){
                let tirar_ponto = $(this).val().replace('.', '');
                let vlr_alterado = tirar_ponto.replace(',', '.');
                let total_val1 = $('.quant1').val() * vlr_alterado;
                let formatado = total_val1.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('.total_val1').val(formatado);
                total = total + total_val1;
                let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('#total_order').val(total_formatado);
            })

            $('.unit_val2').blur(function(){
                let tirar_ponto = $(this).val().replace('.', '');
                let vlr_alterado = tirar_ponto.replace(',', '.');
                let total_val2 = $('.quant2').val() * vlr_alterado;
                let formatado = total_val2.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('.total_val2').val(formatado);
                total = total + total_val2;
                let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('#total_order').val(total_formatado);
            })

            $('.unit_val3').blur(function(){
                let tirar_ponto = $(this).val().replace('.', '');
                let vlr_alterado = tirar_ponto.replace(',', '.');
                let total_val3 = $('.quant3').val() * vlr_alterado;
                let formatado = total_val3.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('.total_val3').val(formatado);
                total = total + total_val3;
                let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('#total_order').val(total_formatado);
            })

            $('.unit_val4').blur(function(){
                let tirar_ponto = $(this).val().replace('.', '');
                let vlr_alterado = tirar_ponto.replace(',', '.');
                let total_val4 = $('.quant4').val() * vlr_alterado;
                let formatado = total_val4.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('.total_val4').val(formatado);
                total = total + total_val4;
                let total_formatado = total.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                $('#total_order').val(total_formatado);
            })

            // Calcular Dia de Entrega
            $('.quant1').blur(function(){
                let id = $('.product_name1').val()
                let quant = $(this).val()
                $.ajax({
                    url:"{{route('day_delivery_calc')}}",
                    type:'get',
                    data:{id:id, quant:quant},
                    dataType:'json',
                    success:function(json){
                        $('.delivery_date1').val(json)
                    },
                });
            })
            $('.quant2').blur(function(){
                let id = $('.product_name2').val()
                let quant = $(this).val()
                $.ajax({
                    url:"{{route('day_delivery_calc')}}",
                    type:'get',
                    data:{id:id, quant:quant},
                    dataType:'json',
                    success:function(json){
                        $('.delivery_date2').val(json)
                    },
                });
            })
            $('.quant3').blur(function(){
                let id = $('.product_name3').val()
                let quant = $(this).val()
                $.ajax({
                    url:"{{route('day_delivery_calc')}}",
                    type:'get',
                    data:{id:id, quant:quant},
                    dataType:'json',
                    success:function(json){
                        $('.delivery_date3').val(json)
                    },
                });
            })
            $('.quant4').blur(function(){
                let id = $('.product_name4').val()
                let quant = $(this).val()
                $.ajax({
                    url:"{{route('day_delivery_calc')}}",
                    type:'get',
                    data:{id:id, quant:quant},
                    dataType:'json',
                    success:function(json){
                        $('.delivery_date4').val(json)
                    },
                });
            })
        </script>
    @endsection

@endsection

