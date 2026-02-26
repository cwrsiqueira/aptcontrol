@extends('layouts.template')

@section('title', 'Caminhões')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Caminhões</h2>

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
                <form method="get" class="form-inline" action="{{ route('trucks.index') }}">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q" id="q"
                            placeholder="Procurar responsável, placa ou modelo" value="{{ $q ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @if (!empty($q))
                        <a class="btn btn-sm btn-secondary ml-2" href="{{ route('trucks.index') }}">Limpar Busca</a>
                    @endif
                </form>
            </div>

            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('trucks.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary" href="{{ route('trucks.create') }}" title="Cadastrar Caminhão">
                        <i class="fas fa-plus"></i> Novo
                    </a>
                @else
                    <button class="btn btn-primary" disabled title="Solicitar Acesso">
                        <i class="fas fa-plus"></i> Novo
                    </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Responsável</th>
                            <th>Capacidade (paletes)</th>
                            <th>Modelo</th>
                            <th>Placa</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trucks as $truck)
                            <tr>
                                <td>
                                    <a href="{{ route('trucks.show', $truck) }}">{{ $truck->responsavel }}</a>
                                </td>
                                <td>{{ $truck->capacidade_paletes }}</td>
                                <td>{{ $truck->modelo ?? '—' }}</td>
                                <td>{{ $truck->placa ?? '—' }}</td>
                                <td class="text-right">
                                    @if (in_array('trucks.view', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('trucks.show', $truck) }}" title="Ver">Ver</a>
                                    @endif
                                    @if (in_array('trucks.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('trucks.edit', $truck) }}" title="Editar">Editar</a>
                                    @endif
                                    @if (in_array('trucks.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form method="POST" action="{{ route('trucks.destroy', $truck) }}" class="d-inline"
                                            onsubmit="return confirm('Excluir este caminhão?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">Excluir</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum caminhão cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($trucks->hasPages())
                <div class="card-footer">{{ $trucks->links() }}</div>
            @endif
        </div>

        <a class="btn btn-light mt-2" href="{{ route('logistica.index') }}">< Logística</a>
    </main>
@endsection
