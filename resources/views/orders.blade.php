@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedidos</h2>

        <form method="get" class="d-flex justify-content-between">
            <div class="form-check m-3">
                <label class="form-check-label">
                    <input onclick="this.form.submit();" type="checkbox" class="form-check-input" name="comp" @if(@$_GET['comp'] === '1') checked @endif value="1">Mostrar Pedidos Concluídos
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
                        <td><a class="btn btn-sm btn-secondary" href="{{ route('orders.show', ['order' => $item->id]) }}">Visualizar</a></td>
                        <td><a class="btn btn-sm btn-secondary" href="{{ route('orders.edit', ['order' => $item->id]) }}">Editar</a></td>
                        <td><button class="btn btn-sm btn-secondary complete_order" data-id="{{$item->id}}">Concluir</button></td>
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