@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <h2>Produtos</h2>

        {{-- Mostra errors --}}
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

        {{-- Cadastra produtos, Busca e Tabela Lista de Produtos Cadastrados --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <form method="get" class="form-inline" action="{{ route('products.index') }}">
                <div class="input-group">
                    <input type="search" class="form-control" name="q" id="q" placeholder="Procurar Produto"
                        value="{{ $q ?? '' }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                @if (!empty($q))
                    <a class="btn btn-sm btn-secondary ml-2" href="{{ route('products.index') }}">Limpar Busca</a>
                @endif
            </form>

            @if (in_array('products.create', $user_permissions) || Auth::user()->is_admin)
                <a class="btn btn-primary" href="{{ route('products.create') }}">Cadastrar Produto</a>
            @else
                <button class="btn btn-primary" disabled title="Solicitar Acesso">Cadastrar Produto</button>
            @endif
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Produto</th>
                            <th>Estoque inicial</th>
                            <th>Produção estima diária</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $item)
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><a href="{{ route('products.show', $item) }}"><?php echo $item['name']; ?></a></td>
                                <td><?php echo number_format($item['current_stock'], 0, '', '.'); ?></td>
                                <td><?php echo number_format($item['daily_production_forecast'], 0, '', '.'); ?></td>
                                <td>
                                    @if (in_array('products.cc', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-warning"
                                            href="{{ route('cc_product', $item->id) }}">Entregas</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" disabled
                                            title="Solicitar Acesso">Entregas</button>
                                    @endif

                                    @if (in_array('products.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('products.edit', $item) }}">Editar</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" disabled
                                            title="Solicitar Acesso">Editar</button>
                                    @endif

                                    @if (in_array('products.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form action="{{ route('products.destroy', $item->id) }}" method="post"
                                            style="display:inline-block"
                                            onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-danger" disabled
                                            title="Solicitar Acesso">Excluir</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $products->links() }}
            </div>
        </div>
    </main>
@endsection
