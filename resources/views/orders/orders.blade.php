@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <h2>Pedidos</h2>

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

        {{-- Cadastra clientes, Busca e Tabela Lista de Pedidos Cadastrados --}}
        <div class="row mb-3">
            <div class="col-sm-7">
                <form method="get" class="row" id="form-search" action="{{ route('orders.index') }}">
                    <div class="col-sm">
                        <div class="input-group w-100">
                            <input type="search" class="form-control" name="q" id="q"
                                placeholder="Busca por número, cliente ou vendedor" value="{{ $q ?? '' }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 d-flex align-items-center">
                        <div class="form-group form-check m-0">
                            <input type="checkbox" @if ($complete_order === '1' || $complete_order === '2') checked @endif class="form-check-input"
                                value="1" id="complete_order" name="complete_order">
                            <label class="form-check-label" for="complete_order">Baixados/cancelados</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-sm">
                @if (!empty($q) || !empty($complete_order))
                    <a class="btn btn-sm btn-secondary ml-2" href="{{ route('orders.index') }}">Limpar Busca</a>
                @endif
            </div>
            <div class="col-sm-3 d-flex justify-content-end">
                @if (in_array('orders.create', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-primary w-80" href="{{ route('orders.create') }}">Cadastrar Pedido</a>
                @else
                    <button class="btn btn-primary w-80" disabled title="Solicitar Acesso">Cadastrar Pedido</button>
                @endif
            </div>
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table" style="text-align:center">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Retirada</th>
                            <th>Vendedor</th>
                            @if ($complete_order == 0)
                                <th>Ações</th>
                            @else
                                <th>Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $item)
                            @php
                                switch ($item->complete_order) {
                                    case '0':
                                        $status = 'Pendente';
                                        $badge = 'info';
                                        break;
                                    case '1':
                                        $status = 'Finalizado';
                                        $badge = 'success';
                                        break;
                                    case '2':
                                        $status = 'Cancelado';
                                        $badge = 'danger';
                                        break;

                                    default:
                                        $status = 'Pendente';
                                        $badge = 'info';
                                        break;
                                }
                            @endphp
                            <tr>
                                <td class="fs-sm">{{ date('d/m/Y', strtotime($item->order_date)) }}</td>
                                <td class="text-left"><a class="fs-sm"
                                        href="{{ route('orders.show', $item) }}">{{ $item->order_number }}</a></td>
                                <td class="cursor-help fs-sm text-left" title="{{ $item->client->name }}">
                                    {{ Str::limit($item->client->name, 30, '...') }}</td>
                                <td class="fs-sm">{{ Str::ucfirst($item->withdraw) }}
                                    {{ Str::lower($item->withdraw) == 'entregar' ? '(CIF)' : '(FOB)' }}
                                </td>
                                <td class="fs-sm">{{ Str::ucfirst($item->seller->name ?? '-') }}</td>

                                @if ($item->complete_order == 0)
                                    <td>
                                        <div class="d-flex flex-column flex-sm-row">
                                            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                <a class="btn btn-sm btn-success mr-1 mb-1 fs-sm"
                                                    href="{{ route('order_products.index', ['order' => $item->id]) }}">ENTREGAR</a>
                                            @else
                                                <button class="btn btn-sm btn-success mr-1 mb-1 fs-sm" disabled
                                                    title="Solicitar Acesso">ENTREGAR</button>
                                            @endif

                                            @if (in_array('orders.cc', $user_permissions) || Auth::user()->is_admin)
                                                <a class="btn btn-sm btn-outline-warning mr-1 mb-1 fs-sm"
                                                    href="{{ route('cc_order', $item) }}">Ver entregas</a>
                                            @else
                                                <button class="btn btn-sm btn-outline-warning mr-1 mb-1 fs-sm" disabled
                                                    title="Solicitar Acesso">Ver entregas</button>
                                            @endif

                                            @if (in_array('orders.view', $user_permissions) || Auth::user()->is_admin)
                                                <a class="btn btn-sm btn-outline-info mr-1 mb-1 fs-sm"
                                                    href="{{ route('order_products.index', ['order' => $item]) }}">Produtos</a>
                                            @else
                                                <button class="btn btn-sm btn-outline-info mr-1 mb-1 fs-sm" disabled
                                                    title="Solicitar Acesso">Produtos</button>
                                            @endif

                                            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                <a class="btn btn-sm btn-outline-primary mr-1 mb-1 fs-sm"
                                                    href="{{ route('orders.edit', $item) }}">Editar</a>
                                            @else
                                                <button class="btn btn-sm btn-outline-primary mr-1 mb-1 fs-sm" disabled
                                                    title="Solicitar Acesso">Editar</button>
                                            @endif

                                            @if (in_array('orders.delete', $user_permissions) || Auth::user()->is_admin)
                                                <form action="{{ route('orders.destroy', $item) }}" method="post"
                                                    style="display:inline-block"
                                                    onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        class="btn btn-sm btn-outline-danger mr-1 mb-1 fs-sm">Excluir</button>
                                                </form>
                                            @else
                                                <button class="btn btn-sm btn-outline-danger mr-1 mb-1 fs-sm" disabled
                                                    title="Solicitar Acesso">Excluir</button>
                                            @endif
                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <div class="badge badge-{{ $badge }}">{{ $status }}</div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $orders->links() }}
            </div>
        </div>
    </main>
@endsection

@section('css')
    <style>
        .cursor-help {
            cursor: help;
        }

        .w-80 {
            width: 80%;
        }

        .fs-sm {
            font-size: 12px;
        }
    </style>
@endsection

@section('js')
    <script>
        document.querySelector('#complete_order').addEventListener('change', function() {
            document.querySelector('#form-search').submit()
        })
    </script>
@endsection
