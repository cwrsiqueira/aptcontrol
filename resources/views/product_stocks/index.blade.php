@extends('layouts.template')

@section('title', 'Estoque - ' . $product->name)

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">
        <h2>Estoque do Produto: {{ $product->name }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-sm-4">
                <form method="get" action="{{ route('products.stocks.index', $product->id) }}">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q"
                            placeholder="Buscar por observação ou estoque" value="{{ $q ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @if (!empty($q))
                        <a class="btn btn-sm btn-secondary ml-2"
                            href="{{ route('products.stocks.index', $product->id) }}">Limpar</a>
                    @endif
                </form>
            </div>

            <div class="col-sm-8 d-flex justify-content-end">
                <a class="btn btn-primary" href="{{ route('products.stocks.create', $product->id) }}">
                    Atualizar estoque
                </a>
                <a class="btn btn-outline-secondary ml-2" href="{{ route('products.index') }}">Voltar</a>
            </div>
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Estoque</th>
                            <th>Data</th>
                            <th>Obs</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $s)
                            <tr>
                                <td>{{ $s->id }}</td>
                                <td>{{ number_format($s->stock, 0, '', '.') }}</td>
                                <td>{{ $s->stock_date ? $s->stock_date->format('d/m/Y') : '-' }}</td>
                                <td class="text-left">{{ $s->notes }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-info"
                                        href="{{ route('products.stocks.show', [$product->id, $s->id]) }}">Visualizar</a>

                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('products.stocks.edit', [$product->id, $s->id]) }}">Editar</a>

                                    <form action="{{ route('products.stocks.destroy', [$product->id, $s->id]) }}"
                                        method="post" style="display:inline-block"
                                        onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $stocks->links() }}
            </div>
        </div>
    </main>
@endsection
