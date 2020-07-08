@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedidos</h2>
        <div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nr.Pedido</th>
                        <th>Dt Pedido</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
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

                        <td><a class="btn btn-sm btn-secondary" href="{{ route('orders.show', ['order' => $item->id]) }}" target="_blank">Visualizar</a></td>
        
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
@endsection