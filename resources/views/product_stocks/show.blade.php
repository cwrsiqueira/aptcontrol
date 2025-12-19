@extends('layouts.template')

@section('title', 'Visualizar registro - Estoque - ' . $product->name)

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <div>
                <h2 class="mb-0">Visualizar registro</h2>
                <small class="text-muted">Produto: {{ $product->name }} | Registro #{{ $stock->id }}</small>
            </div>

            <div>
                <a class="btn btn-outline-secondary" href="{{ route('products.stocks.index', $product->id) }}">Voltar</a>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">ID</div>
                    <div class="col-md-9">{{ $stock->id }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">Estoque</div>
                    <div class="col-md-9">{{ number_format($stock->stock, 0, '', '.') }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">Data</div>
                    <div class="col-md-9">{{ $stock->stock_date ? $stock->stock_date->format('d/m/Y') : '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">Observação</div>
                    <div class="col-md-9">{{ $stock->notes ?: '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">Criado em</div>
                    <div class="col-md-9">{{ $stock->created_at ? $stock->created_at->format('d/m/Y H:i') : '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-3 font-weight-bold">Atualizado em</div>
                    <div class="col-md-9">{{ $stock->updated_at ? $stock->updated_at->format('d/m/Y H:i') : '-' }}</div>
                </div>

                <hr>

                <a class="btn btn-outline-primary" href="{{ route('products.stocks.edit', [$product->id, $stock->id]) }}">
                    Editar
                </a>

                <form action="{{ route('products.stocks.destroy', [$product->id, $stock->id]) }}" method="post"
                    style="display:inline-block" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger">Excluir</button>
                </form>
            </div>
        </div>

    </main>
@endsection
