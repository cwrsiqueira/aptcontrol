@extends('layouts.template')

@section('title', 'Permissões (Itens)')

@section('content')
<main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 page-header">
        <h2 class="mb-0">Permissões <small class="text-muted d-block d-sm-inline" style="font-size:14px;">(Itens)</small></h2>

        <a href="{{ route('permission-items.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Nova permissão
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
            <i class="icon fas fa-ban"></i> Erro!
            <ul class="mb-0">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
            <i class="icon fas fa-check"></i> {{ session('success') }}
        </div>
    @endif

    <div class="card card-lift mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('permission-items.index') }}" class="form-inline">
                <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control mr-2" style="min-width:260px;"
                       placeholder="Buscar por slug, nome ou grupo...">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>

                @if (!empty($q))
                    <a href="{{ route('permission-items.index') }}" class="btn btn-link ml-2">Limpar</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card card-lift">
        <div class="card-header d-flex justify-content-between">
            <h4 class="mb-0">Lista</h4>
            <small class="text-muted">{{ $items->total() }} registros</small>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 90px;">ID</th>
                            <th>Slug</th>
                            <th>Nome</th>
                            <th>Grupo</th>
                            <th style="width: 170px;" class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td><code>{{ $item->slug }}</code></td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->group_name }}</td>
                                <td class="text-right">
                                    <a href="{{ route('permission-items.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('permission-items.destroy', $item->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Confirma a exclusão da permissão?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Nenhuma permissão encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($items->hasPages())
            <div class="card-footer">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</main>
@endsection
