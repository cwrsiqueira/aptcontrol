@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Cadastrar Pedido</h2>

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
                <form action="{{ route('orders.store') }}" method="post" novalidate>
                    @csrf

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="order_number">Número do pedido:</label>
                                <input class="form-control @error('order_number') is-invalid @enderror" type="text"
                                    name="order_number" id="order_number"
                                    value="{{ old('order_number') ?? $seq_order_number }}">
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="order_date">Data:</label>
                                <input class="form-control @error('order_date') is-invalid @enderror" type="date"
                                    name="order_date" id="order_date" value="{{ old('order_date') ?? date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="client_name">Cliente <small>(Digite um novo nome para cadastrar)</small>:</label>
                        <input type="search" class="form-control @error('client_name') is-invalid @enderror"
                            id="client_name" name="client_name" list="lista-clientes" placeholder="Busca cliente..."
                            value="{{ old('client_name') }}">
                        <datalist id="lista-clientes">
                            @foreach ($clients as $item)
                                <option @if (old('client_name') == $item->name) selected @endif value="{{ $item->name }}">
                                </option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="withdraw">Entrega:</label>
                                <select name="withdraw" id="withdraw" class="form-control">
                                    <option @if (old('withdraw') == 'retirar') selected @endif value="retirar">Retirar na
                                        fábica
                                        (FOB)</option>
                                    <option @if (old('withdraw') == 'entregar') selected @endif value="entregar">Entregar na
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
                                    placeholder="Busca vendedor..." value="{{ old('seller_name') }}">
                                <datalist id="lista-vendedores">
                                    @foreach ($sellers as $item)
                                        <option @if (old('seller_name') == $item->name) selected @endif
                                            value="{{ $item->name }}"></option>
                                    @endforeach
                                </datalist>
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
