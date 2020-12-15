@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedidos</h2>

        @if ($errors->has('cannot_exclude') || $errors->has('no-access'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
            <h5>
                <i class="icon fas fa-ban"></i>
                Erro!!!
            </h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="get" class="d-flex justify-content-between">
            <div class="form-check m-3">
                <label class="form-check-label">
                    @if(in_array('16', $user_permissions) || Auth::user()->confirmed_user === 1) 
                    <input onclick="this.form.submit();" type="checkbox" class="form-check-input" name="comp" @if(@$_GET['comp'] === '1') checked @endif value="1">Mostrar Pedidos Concluídos
                    @else 
                    <input disabled title="Solicitar Acesso" type="checkbox" class="form-check-input">Mostrar Pedidos Concluídos
                    @endif
                </label>
            </div>

            <div class="d-flex align-items-center">
                @if(!empty($q))
                <a class="btn btn-sm btn-secondary mr-3" style="width:160px" href="{{route('orders.index')}}">Limpar Busca</a>
                @endif
                <input type="search" class="form-control" name="q" id="q" placeholder="Procurar Pedido" value="{{$q}}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-default">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nr.Pedido</th>
                        <th>Dt Pedido</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
                        <th>Entrega</th>
                        <th colspan="3" style="text-align: center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $item): ?>
                    <tr>
                        <td><?php echo $item['order_number']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($item['order_date'])); ?></td>
                        <td><?php echo $item['name_client']; ?></td>
                        <td>{{$item['payment']}}</td>
                        <td>{{$item['withdraw']}}</td>
                        <td>
                            @if(in_array('17', $user_permissions) || Auth::user()->confirmed_user === 1) 
                            <a class="btn btn-sm btn-secondary" href="{{ route('orders.show', ['order' => $item->id]) }}">Visualizar</a>
                            @else 
                            <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Visualizar</button>
                            @endif
                        </td>
                        @if ($item->complete_order === 1 || $item->complete_order === 2)
                            @if ($item->complete_order === 1)
                            <td colspan="2" style="text-align: center; font-weight:bold;color:green">
                                Entregue
                            </td>
                            @endif
                            @if ($item->complete_order === 2)
                            <td colspan="2" style="text-align: center; font-weight:bold;color:red">
                                Cancelado
                            </td>
                            @endif
                        @else
                            <td>
                                @if(in_array('18', $user_permissions) || Auth::user()->confirmed_user === 1)
                                <a class="btn btn-sm btn-secondary" href="{{ route('orders.edit', ['order' => $item->id]) }}">Editar</a>
                                @else 
                                <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Editar</button>
                                @endif
                            </td>
                            <td>
                                @if(in_array('19', $user_permissions) || Auth::user()->confirmed_user === 1)
                                <a class="btn btn-sm btn-secondary" href="{{ route('orders_conclude', ['order' => $item->id]) }}">Concluir</a>
                                @else 
                                <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Concluir</button>
                                @endif
                            </td>
                        @endif
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            @if ($comp === 1)
                {{$orders->appends(['q' => $q ?? ''])->appends(['comp' => $comp])->links()}}
            @else
                {{$orders->appends(['q' => $q ?? ''])->links()}}
            @endif
        </div>
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
                }
            }

            $('.complete_order').click(function(){
                if (confirm('Confirma a entrega do pedido?')) {
                    let id = $(this).attr('data-id');
                
                    $.ajax({
                        url:"{{route('edit_complete_order')}}",
                        type:'get',
                        data:{id:id},
                        dataType:'json',
                        success:function(json){
                            alert(json);
                            window.location.href = 'orders';
                        },
                    });
                } else {
                    return false;
                }
                
            })
        })
    </script>
@endsection