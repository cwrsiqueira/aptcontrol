@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Pedido</h2>

        <div class="d-flex justify-content-end mb-3">
            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                <a class="btn btn-sm btn-outline-primary"
                    href="{{ route('order_products.index', ['order' => $order->id]) }}">Editar
                    produtos</a>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('orders.update', $order) }}" method="post" novalidate>
                    @method('PUT')
                    @csrf

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="order_number">Número do pedido:</label>
                                <input class="form-control @error('order_number') is-invalid @enderror" type="text"
                                    name="order_number" id="order_number"
                                    value="{{ old('order_number') ?? $order->order_number }}">
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="order_date">Data:</label>
                                <input class="form-control @error('order_date') is-invalid @enderror" type="date"
                                    name="order_date" id="order_date"
                                    value="{{ old('order_date') ?? date('Y-m-d', strtotime($order->order_date)) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="client_name">Cliente <small>(Digite um novo nome para cadastrar)</small>:</label>
                        <input type="search" class="form-control @error('client_name') is-invalid @enderror"
                            id="client_name" name="client_name" list="lista-clientes" placeholder="Busca cliente..."
                            value="{{ $order->client->name }}">
                        <datalist id="lista-clientes">
                            @foreach ($clients as $item)
                                <option value="{{ $item->name }}">
                                </option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="withdraw">Entrega:</label>
                                <select name="withdraw" id="withdraw" class="form-control">
                                    <option @if ((old('withdraw') ?? Str::lower($order->withdraw)) == 'retirar') selected @endif value="retirar">Retirar na
                                        fábrica
                                        (FOB)</option>
                                    <option @if ((old('withdraw') ?? Str::lower($order->withdraw)) == 'entregar') selected @endif value="entregar">Entregar na
                                        obra (CIF)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="seller_name">Vendedor <small>(Digite um novo nome para
                                        cadastrar)</small>:</label>
                                <input type="search" class="form-control @error('seller_name') is-invalid @enderror"
                                    id="seller_name" name="seller_name" list="lista-vendedores"
                                    placeholder="Busca vendedor..." value="{{ $order->seller->name ?? '' }}">
                                <datalist id="lista-vendedores">
                                    @foreach ($sellers as $item)
                                        <option value="{{ $item->name ?? '' }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="payment">Pagamento:</label>
                                <select name="payment" id="payment" class="form-control">
                                    <option @if (old('payment') ?? $order->payment == 'Aberto') selected @endif value="Aberto">Aberto</option>
                                    <option @if (old('payment') ?? $order->payment == 'Parcial') selected @endif value="Parcial">Parcial
                                    </option>
                                    <option @if (old('payment') ?? $order->payment == 'Total') selected @endif value="Total">Total</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light" href="{{ route('orders.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
