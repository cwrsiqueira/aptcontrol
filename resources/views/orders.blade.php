@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedidos</h2>

        <div class="d-flex justify-content-between">

            <div></div>
            
            <form method="get" class="d-flex align-items-center m-3">
                @if(!empty($q))
                <a class="btn btn-sm btn-secondary m-3" href="{{route('orders.index')}}">Limpar Busca</a>
                @endif
                <input type="search" class="form-control m-3" name="q" id="q" placeholder="Procurar Pedido" value="{{$q}}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-default">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

        </div>

        <div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nr.Pedido</th>
                        <th>Dt Pedido</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
                        <th>Entrega</th>
                        <th></th>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            {{$orders->appends(['q' => $q ?? ''])->links()}}
        </div>
    </main>
@endsection