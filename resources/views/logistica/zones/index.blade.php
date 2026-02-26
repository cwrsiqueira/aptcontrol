@extends('layouts.template')

@section('title', 'Zonas')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Zonas</h2>

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
            <div class="col-sm">
                <form method="get" class="form-inline" action="{{ route('zones.index') }}">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q" id="q"
                            placeholder="Procurar zona ou bairro" value="{{ $q ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @if (!empty($q))
                        <a class="btn btn-sm btn-secondary ml-2" href="{{ route('zones.index') }}">Limpar Busca</a>
                    @endif
                </form>
            </div>

            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('zones.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary" href="{{ route('zones.create') }}" title="Cadastrar Zona">
                        <i class="fas fa-plus"></i> Nova
                    </a>
                @else
                    <button class="btn btn-primary" disabled title="Solicitar Acesso">
                        <i class="fas fa-plus"></i> Nova
                    </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Zona</th>
                            <th>Bairros</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($zones as $zone)
                            <tr>
                                <td>
                                    <a href="{{ route('zones.show', $zone) }}">{{ $zone->nome }}</a>
                                </td>
                                <td>
                                    @if ($zone->bairros->count() > 0)
                                        {{ $zone->bairros->pluck('bairro_nome')->implode(', ') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if (in_array('zones.view', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('zones.show', $zone) }}" title="Ver">Ver</a>
                                    @endif
                                    @if (in_array('zones.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('zones.edit', $zone) }}" title="Editar">Editar</a>
                                    @endif
                                    @if (in_array('zones.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form method="POST" action="{{ route('zones.destroy', $zone) }}" class="d-inline"
                                            onsubmit="return confirm('Excluir esta zona?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">Excluir</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Nenhuma zona cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($zones->hasPages())
                <div class="card-footer">{{ $zones->links() }}</div>
            @endif
        </div>

        <a class="btn btn-light mt-2" href="{{ route('logistica.index') }}">< Logística</a>
    </main>
@endsection
