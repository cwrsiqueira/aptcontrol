@extends('layouts.template')

@section('title', 'Vendedores')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Vendedores</h2>

        {{-- Alerts de erro/sucesso --}}
        @if ($errors->has('cannot_exclude') || $errors->has('no-access'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5><i class="icon fas fa-ban"></i> Erro!!!</h5>
                <ul class="mb-0">
                    @if ($errors->has('cannot_exclude'))
                        <li>{{ $errors->first('cannot_exclude') }}</li>
                    @endif
                    @if ($errors->has('no-access'))
                        <li>{{ $errors->first('no-access') }}</li>
                    @endif
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
                <form method="get" class="form-inline" action="{{ route('sellers.index') }}">
                    <div class="input-group">
                        <input type="search" class="form-control" name="q" id="q"
                            placeholder="Procurar Vendedor" value="{{ $q ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    @if (!empty($q))
                        <a class="btn btn-sm btn-secondary ml-2" href="{{ route('sellers.index') }}">Limpar Busca</a>
                    @endif
                </form>
            </div>

            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('sellers.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary w-80" href="{{ route('sellers.create') }}">Cadastrar Vendedor</a>
                @else
                    <button class="btn btn-primary w-80" disabled title="Solicitar Acesso">Cadastrar Vendedor</button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo de Contato</th>
                            <th>Contato</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sellers as $seller)
                            <tr>
                                <td class="text-left">
                                    <a class="ml-3"
                                        href="{{ route('sellers.show', $seller->id) }}">{{ $seller->name }}</a>
                                </td>
                                <td class="text-left">
                                    {{ Str::ucfirst($seller->contact_type) }}
                                </td>
                                <td>{{ $seller->contact_value }}</td>
                                <td>
                                    @if (in_array('sellers.cc', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-warning"
                                            href="{{ route('cc_seller', $seller->id) }}">Ver entregas</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning" disabled title="Solicitar Acesso">Ver
                                            entregas</button>
                                    @endif

                                    @if (in_array('sellers.update', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('sellers.edit', $seller->id) }}">Editar</a>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" disabled
                                            title="Solicitar Acesso">Editar</button>
                                    @endif

                                    @if (in_array('sellers.delete', $user_permissions) || Auth::user()->is_admin)
                                        <form action="{{ route('sellers.destroy', $seller->id) }}" method="post"
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
            </div>

            @if ($sellers->hasPages())
                <div class="card-footer">
                    {{ $sellers->links() }}
                </div>
            @endif
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
