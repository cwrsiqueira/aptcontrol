@extends('layouts.template')

@section('title', 'Produtos do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        {{-- Título + ações --}}
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Produtos do Pedido</h2>
            <div>
                <a class="btn btn-sm btn-light" href="{{ route('orders.index') }}">Voltar</a>
            </div>
        </div>

        {{-- Cabeçalho do pedido (bonito e compacto) --}}
        <div class="card card-lift mt-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="mb-2">
                        <div class="text-muted small">Número do Pedido</div>
                        <div class="display-5 font-weight-bold">#{{ $order->order_number }}</div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center">
                        <span class="badge badge-primary mr-2 mb-2">
                            {{ date('d/m/Y', strtotime($order->order_date)) }}
                        </span>
                        <span class="badge badge-info mr-2 mb-2">
                            {{ ucfirst(strtolower($order->withdraw)) }}
                            ({{ strtolower($order->withdraw) === 'entregar' ? 'CIF' : 'FOB' }})
                        </span>
                    </div>
                </div>

                <hr class="my-3">

                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted">Cliente</dt>
                    <dd class="col-sm-9">{{ optional($order->client)->name }}</dd>

                    <dt class="col-sm-3 text-muted">Vendedor</dt>
                    <dd class="col-sm-9">{{ optional($order->seller)->name }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-sm d-flex justify-content-end mt-3">
            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                <a class="btn btn-primary" href="{{ route('order_products.create', ['order' => $order]) }}">Adicionar
                    produto</a>
            @else
                <button class="btn btn-primary" disabled title="Solicitar Acesso">Adicionar produto</button>
            @endif
        </div>

        <div class="card card-lift mt-3">
            <div class="card-header">
                <h5 class="mb-0">Itens</h5>
            </div>
            <div class="table-responsive tableFixHead">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Produto</th>
                            <th class="text-right">Quantidade</th>
                            <th class="text-right">Entrega</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order_products as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td class="text-right">{{ number_format($qtd, 0, '', '.') }}</td>
                                <td>{{ $entrega ? date('d/m/Y', strtotime($entrega)) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Nenhum item encontrado para este pedido.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection

@section('css')
    <style>
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .tableFixHead {
            max-height: 60vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .display-5 {
            font-size: 2rem;
            line-height: 1.1;
        }

        .font-weight-semibold {
            font-weight: 600;
        }
    </style>
@endsection
