@extends('layouts.template')

@section('title', 'Detalhes do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Pedido</h2>
            <div>
                <a class="btn btn-sm btn-light" href="{{ route('orders.index') }}">
                    < Pedidos</a>
            </div>
        </div>

        @php
            switch ($order->complete_order) {
                case '0':
                    $status = 'Pendente';
                    $badge = 'info';
                    break;
                case '1':
                    $status = 'Finalizado';
                    $badge = 'success';
                    break;
                case '2':
                    $status = 'Cancelado';
                    $badge = 'danger';
                    break;

                default:
                    $status = 'Pendente';
                    $badge = 'info';
                    break;
            }
        @endphp

        <div class="card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-10">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Número</dt>
                            <dd class="col-sm-9">{{ $order->order_number }}</dd>

                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9">
                                <div class="badge badge-{{ $badge }}">{{ $status }}</div>
                            </dd>

                            <dt class="col-sm-3">Data</dt>
                            <dd class="col-sm-9">{{ date('d/m/Y', strtotime($order->order_date)) }}</dd>

                            <dt class="col-sm-3">Cliente</dt>
                            <dd class="col-sm-9">{{ $order->client->name }}</dd>

                            <dt class="col-sm-3">Entrega</dt>
                            <dd class="col-sm-9">{{ Str::ucfirst($order->withdraw) }}
                                ({{ Str::lower($order->withdraw) === 'entregar' ? 'CIF' : 'FOB' }})</dd>

                            <dt class="col-sm-3">Vendedor</dt>
                            <dd class="col-sm-9">{{ $order->seller->name ?? '-' }}</dd>

                            <dt class="col-sm-3">Criado em</dt>
                            <dd class="col-sm-9">{{ optional($order->created_at)->format('d/m/Y H:i') }}</dd>

                            <dt class="col-sm-3">Atualizado em</dt>
                            <dd class="col-sm-9">{{ optional($order->updated_at)->format('d/m/Y H:i') }}</dd>
                        </dl>
                    </div>
                    <div class="col-sm-2">
                        @if ($order->complete_order != 2)
                            @if (in_array('orders.view', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.edit', $order) }}">Editar
                                    pedido</a>
                            @endif
                        @else
                            @if (in_array('orders.delete', $user_permissions) || Auth::user()->is_admin)
                                <form action="{{ route('orders.destroy', $order) }}" method="post"
                                    style="display:inline-block"
                                    onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Excluir pedido</button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-danger" disabled title="Solicitar Acesso">Excluir
                                    pedido</button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Data da entrega</th>
                    <th>
                        @if ($order->complete_order != 2)
                            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('order_products.index', ['order' => $order->id]) }}">Editar
                                    produtos</a>
                            @endif
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order_products as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-end">{{ number_format($item->quant, 0, '', '.') }}</td>
                        <td>{{ date('d/m/Y', strtotime($item->delivery_date == '1970-01-01' ? $item->created_at : $item->delivery_date)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
@endsection
