@extends('layouts.template')

@section('title', 'Clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <h2>Clientes</h2>

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

        {{-- Cadastra clientes, Busca e Tabela Lista de Clientes Cadastrados --}}
        <div class="row mb-3">
            <div class="col-sm-4">
                <form method="get" class="form-inline" action="{{ route('clients.index') }}">
                    <div class="input-group w-100">
                        <input type="search" class="form-control" name="q" id="q"
                            placeholder="Busca por nome ou categoria" value="{{ $q ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-sm">
                @if (!empty($q))
                    <a class="btn btn-sm btn-secondary ml-2" href="{{ route('clients.index') }}">Limpar Busca</a>
                @endif
            </div>
            <div class="col-sm-3">
                <div class="row">
                    <div class="col-sm-12 text-right mb-2">
                        @if (in_array('clients.create', $user_permissions) || Auth::user()->is_admin)
                            <a class="btn btn-primary w-80" href="{{ route('clients.create') }}">Cadastrar Cliente</a>
                        @else
                            <button class="btn btn-primary w-80" disabled title="Solicitar Acesso">Cadastrar
                                Cliente</button>
                        @endif
                    </div>
                    <div class="col-sm-12 text-right">
                        @if (in_array('menu-categorias', $user_permissions) || Auth::user()->is_admin)
                            <a class="btn btn-secondary w-80" href="{{ route('categories.index') }}">Categorias de
                                clientes</a>
                        @else
                            <button class="btn btn-secondary w-80" disabled title="Solicitar Acesso">Categorias de
                                clientes</button>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Cliente</th>
                            <th>Categoria</th>
                            <th>Contato</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td class="text-left"><a href="{{ route('clients.show', $item) }}">{{ $item->name }}</a>
                                </td>
                                <td>{{ $item->category->name }}</td>
                                <td>{{ $item->contact }}</td>
                                <td>
                                    @if (in_array('clients.cc', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-warning"
                                            href="{{ route('cc_client', $item->id) }}">Ver entregas</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" disabled title="Solicitar Acesso">Ver
                                            entregas</button>
                                    @endif

                                    @if (in_array('clients.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('clients.edit', $item) }}">Editar</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" disabled
                                            title="Solicitar Acesso">Editar</button>
                                    @endif

                                    @if (in_array('clients.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form action="{{ route('clients.destroy', $item->id) }}" method="post"
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
                {{ $clients->links() }}
            </div>
        </div>
    </main>
@endsection

@section('css')
    <style>
        .w-80 {
            width: 80%;
        }
    </style>
@endsection
