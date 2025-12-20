@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <h2>Produtos</h2>

        {{-- Mostra errors --}}
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

        {{-- Cadastra produtos, Busca e Tabela Lista de Produtos Cadastrados --}}
        <div class="row mb-3">
            <div class="col-sm">
                <form method="get" class="form-inline" action="{{ route('products.index') }}">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q" id="q"
                            placeholder="Busca por Produto" value="{{ $q ?? '' }}">
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
            </div>
            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('products.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary w-80" href="{{ route('products.create') }}">Cadastrar Produto</a>
                @else
                    <button class="btn btn-primary w-80" disabled title="Solicitar Acesso">Cadastrar Produto</button>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Produto</th>
                            <th>Estoque Atualizado</th>
                            <th>Produção estimada diária</th>
                            <th>Falta entregar</th>
                            <th>Próxima entrega</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td class="text-left">
                                    @php
                                        $fullName = (string) $item->name;
                                        $shortName = \Illuminate\Support\Str::limit($fullName, 40, '...');
                                    @endphp

                                    <a href="{{ route('products.show', $item) }}" title="{{ $fullName }}">
                                        <span class="product-name">{{ $shortName }}</span>
                                    </a>
                                </td>
                                <td>{{ $item->current_stock ? number_format($item->current_stock, 0, '', '.') : 0 }}</td>
                                <td>{{ number_format($item->daily_production_forecast, 0, '', '.') }}</td>
                                <td>{{ number_format($item->quant_total, 0, '', '.') }}</td>
                                <td>{{ $item->delivery_in ? \Carbon\Carbon::parse($item->delivery_in)->format('d/m/Y') : '--/--/----' }}
                                </td>
                                <td>
                                    @if (in_array('products.stock', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ route('products.stocks.index', $item->id) }}">Estoque</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled
                                            title="Solicitar Acesso">Estoque</button>
                                    @endif
                                    @if (in_array('products.cc', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-warning"
                                            href="{{ route('cc_product', $item->id) }}">Ver entregas</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" disabled title="Solicitar Acesso">Ver
                                            entregas</button>
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

@section('css')
    <style>
        .w-80 {
            width: 80%;
        }

        .product-name {
            display: inline-block;
            max-width: 360px;
            /* ajuste se quiser */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
    </style>
@endsection
