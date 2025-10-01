@extends('layouts.template')

@section('title', 'Categorias de clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <div class="d-flex">
            <h2>Categorias de clientes</h2>
            <div class="col-sm" style="text-align: right">
                <a href="{{ route('clients.index') }}">
                    < Clientes</a> / Categorias
            </div>
        </div>

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

        <div class="row d-flex justify-content-end align-items-center">
            <div class="col-sm-3">
                <div class="mb-3 text-right">
                    @if (in_array('categories.create', $user_permissions) || Auth::user()->is_admin)
                        <a class="btn btn-primary w-80" href="{{ route('categories.create') }}">Cadastrar Categoria</a>
                    @else
                        <button class="btn btn-primary w-80" disabled title="Solicitar Acesso">Cadastrar Categoria</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Categoria</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td><a href="{{ route('categories.show', $item) }}">{{ $item->name }}</a></td>
                                <td>

                                    @if (in_array('categories.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('categories.edit', $item) }}">Editar</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" disabled
                                            title="Solicitar Acesso">Editar</button>
                                    @endif

                                    @if ($item->name === 'Padrão')
                                        <button class="btn btn-sm btn-outline-danger" disabled
                                            title="A categoria Padrão não pode ser excluída!">Excluir</button>
                                    @elseif(in_array('categories.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form action="{{ route('categories.destroy', $item) }}" method="post"
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
                {{ $categories->links() }}
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
