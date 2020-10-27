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
        <form action="{{route('orders.store')}}" method="post" id="form">
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

                            <th colspan="1" style="text-align: center">
                                <a class="add_line" style='display:none;' href='#' data-toggle='tooltip' title='Incluir Produtos!'>
                                    <i class="material-icons" style="font-size:32px;color:blue">add_shopping_cart</i>
                                </a>
                                <br>
                                <small class="order_number_align_size" style="color:transparent;"></small>
                            </th>

                        </tr>
                    </thead>
                </table>
                <hr>
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

                    $.ajax({
                        url:"{{route('add_order')}}",
                        type:'get',
                        data:$('#form').serialize(),
                        dataType:'json',
                        success:function(json){
                            window.location.href = BASE_URL + 'orders/' + json + '/edit?new=1';
                        },
                    });

                });
            });
        </script>
    @endsection

@endsection

