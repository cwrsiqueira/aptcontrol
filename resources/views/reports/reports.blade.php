@extends('layouts.template')

@section('title', 'Relatórios')

@section('content')
    @php
        // Evita erro se o controller ainda não injetar as coleções:
        $products = $products ?? collect();
        $clients = $clients ?? collect();
        $orders = $orders ?? collect();
        $sellers = $sellers ?? collect();
    @endphp

    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        {{-- Cabeçalho --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Relatórios</h2>
        </div>

        {{-- GRID DE RELATÓRIOS --}}
        <div class="row reports-grid">
            {{-- Por Produto --}}
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card report-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-cubes mr-2"></i>
                        <h4 class="mb-0">Por produto</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Pode filtrar por <strong>categoria de cliente</strong>.
                        </p>

                        <form class="js-dyn-route" data-url="{{ route('cc_product', 0) }}">
                            <div class="form-group">
                                <label for="selProduct" class="mb-1">Selecione o produto</label>
                                <select id="selProduct" class="form-control js-entity" data-entity="product" required>
                                    <option value="" selected disabled>— escolher —</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name ?? '#' . $product->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-action">
                                Abrir relatório
                                <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Por Cliente --}}
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card report-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-user-friends mr-2"></i>
                        <h4 class="mb-0">Por cliente</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Pode filtrar por <strong>produto</strong>.
                        </p>

                        <form class="js-dyn-route" data-url="{{ route('cc_client', 0) }}">
                            <div class="form-group">
                                <label for="selClient" class="mb-1">Selecione o cliente</label>
                                <select id="selClient" class="form-control js-entity" data-entity="client" required>
                                    <option value="" selected disabled>— escolher —</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name ?? '#' . $client->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-action">
                                Abrir relatório
                                <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Por Pedido --}}
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card report-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-file-invoice mr-2"></i>
                        <h4 class="mb-0">Por pedido</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Pode filtrar por <strong>produto</strong>.
                        </p>

                        <form class="js-dyn-route" data-url="{{ route('cc_order', 0) }}">
                            <div class="form-group">
                                <label for="selOrder" class="mb-1">Selecione o pedido</label>
                                <select id="selOrder" class="form-control js-entity" data-entity="order" required>
                                    <option value="" selected disabled>— escolher —</option>
                                    @foreach ($orders as $order)
                                        <option value="{{ $order->order_number ?? $order->id }}">
                                            #{{ $order->order_number ?? $order->id }}
                                            @if (!empty($order->name_client))
                                                — {{ $order->name_client }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-action">
                                Abrir relatório
                                <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Por Vendedor --}}
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card report-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-user-tie mr-2"></i>
                        <h4 class="mb-0">Por vendedor</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Pode filtrar por <strong>produto</strong>.
                        </p>

                        <form class="js-dyn-route" data-url="{{ route('cc_seller', 0) }}">
                            <div class="form-group">
                                <label for="selSeller" class="mb-1">Selecione o vendedor</label>
                                <select id="selSeller" class="form-control js-entity" data-entity="seller" required>
                                    <option value="" selected disabled>— escolher —</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name ?? '#' . $seller->id }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-action">
                                Abrir relatório
                                <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Por Data --}}
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card report-card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-calendar-day mr-2"></i>
                        <h4 class="mb-0">Por data</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Pode filtrar por <strong>produto</strong>, <strong>cliente</strong>,
                            <strong>pedido</strong> e <strong>vendedor</strong>.
                        </p>

                        <a href="{{ route('cc_data') }}" class="btn btn-secondary btn-action">
                            Abrir relatório por data
                            <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('css')
    <style>
        .page-header h2 {
            font-weight: 600;
        }

        .report-card {
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .1);
        }

        .report-card .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f3f5;
        }

        .report-card .card-header i {
            opacity: .8;
        }

        .btn-action {
            min-width: 180px;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Submete cards que precisam montar rota com ID:
        // Usa data-url com route('nome', 0) e substitui o /0 pelo ID escolhido.
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form.js-dyn-route').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const select = form.querySelector('.js-entity');
                    const id = select && select.value ? String(select.value) : '';
                    if (!id) {
                        alert('Selecione um item para abrir o relatório.');
                        return;
                    }
                    const baseUrl = form.getAttribute('data-url'); // ex.: /cc_product/0
                    if (!baseUrl) return;

                    // troca /0 por /{id} preservando querystring (se houver)
                    const url = baseUrl.replace(/\/0(\b|$)/, '/' + encodeURIComponent(id));
                    window.location.href = url;
                });
            });
        });
    </script>
@endpush
