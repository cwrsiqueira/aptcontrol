@extends('layouts.template')

@section('title', 'Atualizar estoque - ' . $product->name)

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <div>
                <h2 class="mb-0">Atualizar estoque</h2>
                <small class="text-muted">Produto: {{ $product->name }}</small>
            </div>

            <div>
                <a class="btn btn-outline-secondary" href="{{ route('products.stocks.index', $product->id) }}">Voltar</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card bg-light">
            <div class="card-body">
                <form method="POST" action="{{ route('products.stocks.store', $product->id) }}">
                    @csrf

                    @include('product_stocks._form')

                    <button type="submit" class="btn btn-success">Salvar</button>
                    <a class="btn btn-secondary" href="{{ route('products.stocks.index', $product->id) }}">Cancelar</a>
                </form>
            </div>
        </div>

    </main>
@endsection
