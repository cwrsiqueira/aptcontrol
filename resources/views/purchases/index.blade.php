{{-- Lista e filtra os pedidos de compra. --}}
@extends('layouts.template')

@section('title', 'Compras')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">
        <h2>Pedidos de Compra</h2>

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

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-sm-9">
                <form method="GET" action="{{ route('purchases.index') }}" class="form-row">
                    <div class="col-sm-6 mb-2">
                        <div class="input-group">
                            <input type="search" class="form-control" name="q" value="{{ $q }}"
                                placeholder="Buscar por número, solicitante ou descrição">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <select class="form-control" name="status" onchange="this.form.submit()">
                            <option value="">Todos os status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @if ($status === $value) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2 mb-2">
                        @if ($q !== '' || $status !== '')
                            <a class="btn btn-secondary" href="{{ route('purchases.index') }}">Limpar</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('purchases.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary" href="{{ route('purchases.create') }}">
                        <i class="fas fa-plus"></i> Novo
                    </a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Número</th>
                            <th>Data</th>
                            <th>Solicitante</th>
                            <th>Status</th>
                            <th>Menor orçamento</th>
                            <th>Responsável</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->number }}</td>
                                <td>{{ $purchase->request_date->format('d/m/Y') }}</td>
                                <td>{{ optional($purchase->requester)->name ?? 'Usuário removido' }}</td>
                                <td><span class="badge badge-{{ $purchase->status_badge }}">{{ $purchase->status_label }}</span></td>
                                <td>
                                    {{ $purchase->quotes_min_amount !== null
                                        ? 'R$ ' . number_format($purchase->quotes_min_amount, 2, ',', '.')
                                        : '—' }}
                                </td>
                                <td>{{ optional($purchase->creator)->name ?? 'Usuário removido' }}</td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchases.show', $purchase) }}">Ver</a>
                                    @if ($purchase->canEdit() && (in_array('purchases.update', $user_permissions) || Auth::user()->is_admin))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('purchases.edit', $purchase) }}">Editar</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhum pedido de compra encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($purchases->hasPages())
                <div class="card-footer">{{ $purchases->links() }}</div>
            @endif
        </div>
    </main>
@endsection
