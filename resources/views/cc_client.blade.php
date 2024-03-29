@extends('layouts.estilos')

@section('title', 'C/C Cliente')

@section('content')

    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Cliente</h2>
        <div class="row">
            <div class="card col-md-6 m-3">
                <div class="card-header"><span>{{$client->name}}</span></div>
                <form action="{{route('cc_client', ['id' => $client->id])}}" method="get">
                    <div class="card-body">
                        <ul>
                            @foreach ($product_total as $key => $value)
                                <input class="mr-1" type="checkbox" name="por_produto[]"  value="{{$value['id']}}" @if(!empty($_GET['por_produto']) && in_array($value['id'], $_GET['por_produto'])) checked @endif>Total de {{$key}}: {{number_format($value['qt'], 0, '', '.')}} <br>
                            @endforeach
                            <br>
                            <input class="mr-1" type="checkbox" name="entregas"  value="1" @if(!empty($_GET['entregas'])) checked @endif> Mostrar entregas Realizadas <br>
                        </ul>
                        {{-- <label for="">De:</label>
                        <input type="date" name="date_ini" value="{{date('Y-m-d', strtotime($_GET['date_ini'] ?? date('Y-m-01')))}}">
                        <label for="">Até:</label>
                        <input type="date" name="date_fin" value="{{date('Y-m-d', strtotime($_GET['date_fin'] ?? date('Y-m-t')))}}"> --}}
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{route('cc_client', ['id' => $client->id])}}" id="clean_search">Limpar Filtro</a>
                    </div>
                </form>
            </div>
            <div class="col-md-3 m-3">
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="javascript:history.go(-1);" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
                    <a class="btn btn-sm btn-secondary" id="btn_sair" href="{{route('clients.index')}}">Sair</a>
                </div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Pedido</th>
                    <th>Produto</th>
                    <th>Quant</th>
                    <th>Saldo</th>
                    <th>Entrega</th>
                    <th class="btn_acoes" colspan="2">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr 
                    @if($item->saldo < 0) 
                    style="color:green;font-weight:bold;" 
                    @elseif( $item->saldo > 0 && $item->delivery_date < date('Y-m-d') ) 
                    style="color:red;font-weight:bold;" 
                    @else 
                    style="color:#777;font-weight:bold;" 
                    @endif >
                    
                        <td>{{date('d/m/Y', strtotime($item->order_date))}}</td>
                        <td>{{$item->order_id}}</td>
                        <td>{{$item->product_name}}</td>
                        <td>{{number_format($item->quant, 0, '', '.')}}</td>
                        <td>{{number_format($item->saldo, 0, '', '.')}}</td>
                        <td>
                            @if ($item->quant < 0)
                                {{date('d/m/Y', strtotime($item->created_at))}}
                            @else
                                {{date('d/m/Y', strtotime($item->delivery_date))}}
                            @endif
                        </td>
                        <td class="btn_acoes">
                            @if(in_array('18', $user_permissions) || Auth::user()->confirmed_user === 1)
                            <a class="btn btn-sm btn-secondary" href="{{ route('orders.edit', ['order' => $item->orders_order_id]) }}">Editar</a>
                            @else 
                            <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Editar</button>
                            @endif
                        </td>
                        <td class="btn_acoes">
                            @if(in_array('19', $user_permissions) || Auth::user()->confirmed_user === 1)
                            <a class="btn btn-sm btn-secondary" href="{{ route('orders_conclude', ['order' => $item->orders_order_id]) }}">Concluir</a>
                            @else 
                            <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Concluir</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- {{$data->links()}} --}}
    </main>
@endsection

@section('js')
    <script>
        $(function(){
            
            if( window.localStorage ) {

                if( !localStorage.getItem( 'firstLoad' ) ) {
                    localStorage[ 'firstLoad' ] = true;
                    window.location.reload();
                } else {
                    localStorage.removeItem( 'firstLoad' );
                    $('html,body').scrollTop(0);
                }
            }

            $('#btn_imprimir').click(function(){
                $(this).hide();
                $('#btn_voltar').hide();
                $('#btn_sair').hide();
                $('#search').hide();
                $('#clean_search').hide(); 
                $('.btn_acoes').hide(); 
                window.print();
                javascript:history.go(0);
            })
        })
    </script>
@endsection

