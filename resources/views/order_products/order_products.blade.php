@extends('layouts.template')

@section('title', 'Detalhes do Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('error') }}
            </div>
        @endif

        {{-- Título + Voltar (compacto) --}}
        <div class="d-flex justify-content-between align-items-center page-header mb-2">
            <h2 class="page-title mb-0">Detalhes do Pedido</h2>
            <a class="btn btn-sm btn-light" href="{{ route('orders.index') }}">
                < Pedidos</a>
        </div>

        {{-- ITENS (PRIORIDADE NO TOPO) --}}
        <div class="card card-lift items-card">
            <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex flex-wrap align-items-center">
                    <strong class="mr-2">#{{ $order->order_number }}</strong>

                    {{-- Data (maior/legível, mas ainda compacto) --}}
                    <span class="meta-chip mr-2">
                        {{ date('d/m/Y', strtotime($order->order_date)) }}
                    </span>

                    {{-- Retirada / CIF-FOB (legível) --}}
                    @php $isCif = strtolower($order->withdraw) === 'entregar'; @endphp
                    <span class="meta-chip {{ $isCif ? 'meta-cif' : 'meta-fob' }} mr-2">
                        {{ ucfirst(strtolower($order->withdraw)) }} ({{ $isCif ? 'CIF' : 'FOB' }})
                    </span>
                </div>

                <div class="text-right">
                    @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                        <a class="btn btn-sm btn-primary" href="{{ route('order_products.create', ['order' => $order]) }}">
                            Adicionar produto
                        </a>
                    @else
                        <button class="btn btn-sm btn-primary" disabled title="Solicitar Acesso">
                            Adicionar produto
                        </button>
                    @endif
                </div>
            </div>

            {{-- INFO DO PEDIDO (compacto, abaixo da lista) --}}
            <div class="card card-lift">
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md mb-1">
                            <span class="muted-label">Cliente</span>
                            <div class="text-body font-weight-bold">{{ optional($order->client)->name }}</div>
                            <div class="d-flex jalign-items-center mb-3 small">
                                <input type="checkbox" name="fav-client" id="fav-client"
                                    data-url="{{ route('clients.toggle_favorite', $order->client->id) }}"
                                    @if ($order->client->is_favorite) checked @endif>
                                <label for="fav-client"
                                    class="fav-client @if ($order->client->is_favorite) is-fav-client @endif d-inline-block my-0 mx-1">Cliente
                                    aguardando antecipação
                                </label>
                            </div>
                        </div>
                        <div class="col-md mb-1">
                            <span class="muted-label">Vendedor</span>
                            <div class="text-body">{{ optional($order->seller)->name }}</div>
                        </div>
                        <div class="col-md mb-1">
                            <span class="muted-label">Saldos</span>
                            <ul>
                                @foreach ($saldo_produtos as $item)
                                    <li class="text-muted">{{ $item->product->name }}:
                                        <ul class="font-weight-bold">{{ number_format($item->saldo_positivo, 0, '', '.') }}
                                            -
                                            {{ number_format($item->saldo_positivo - $item->saldo, 0, '', '.') }} =
                                            {{ number_format($item->saldo, 0, '', '.') }} <br></ul>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-lift mb-5">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-light sticky-header">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Produto</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-right">Entrega</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order_products as $item)
                                @if ($item->quant > 0)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->product->name }}</td>
                                        <td class="text-right">{{ number_format($item->saldo, 0, '', '.') }}</td>
                                        <td class="text-right d-flex flex-column align-items-end">
                                            {{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}
                                            <span
                                                class="badge badge-danger badge-client-name @if (!$item->favorite_delivery) d-none @endif"
                                                style="width: fit-content;">Data
                                                fixada</span>
                                        </td>
                                        <td class="text-right">
                                            @if ($item->saldo > 0)
                                                @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                    <a class="btn btn-sm btn-success"
                                                        href="{{ route('order_products.delivery', $item) }}">ENTREGAR</a>
                                                @else
                                                    <button class="btn btn-sm btn-success" disabled
                                                        title="Solicitar Acesso">ENTREGAR</button>
                                                @endif
                                            @else
                                                @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                    <a class="btn btn-sm btn-outline-warning"
                                                        href="{{ route('order_products.delivery', $item) }}">Ver
                                                        histórico</a>
                                                @else
                                                    <button class="btn btn-sm btn-outline-warning" disabled
                                                        title="Solicitar Acesso">Ver histórico</button>
                                                @endif
                                            @endif

                                            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('order_products.edit', $item) }}">Editar</a>
                                            @else
                                                <button class="btn btn-sm btn-outline-primary" disabled
                                                    title="Solicitar Acesso">Editar</button>
                                            @endif

                                            @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                                <form action="{{ route('order_products.destroy', $item) }}" method="post"
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
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum item encontrado para este
                                        pedido.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('css')
    <style>
        /* elevação sutil nos cards */
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        /* header geral mais enxuto */
        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .page-header {
            margin-bottom: .25rem;
        }

        /* cabeçalho dos itens: chips de metadados */
        .meta-chip {
            display: inline-block;
            padding: .2rem .5rem;
            font-size: .95rem;
            font-weight: 600;
            border-radius: .375rem;
            background: #f1f3f5;
            color: #495057;
            line-height: 1.1;
        }

        .meta-cif {
            background: #e6f4ea;
            color: #0f5132;
        }

        /* verde suave */
        .meta-fob {
            background: #e7f1ff;
            color: #0b4a8b;
        }

        /* azul suave */

        /* botão menor já vem de btn-sm; reforço de proporção */
        .items-card .btn.btn-sm {
            padding: .25rem .5rem;
        }

        /* tabela prioritária: cabeçalho grudado e altura boa para caber acima da dobra */
        .tableFixHead {
            max-height: 60vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* linhas */
        tbody tr:hover {
            background-color: #f6f9fc;
            cursor: pointer;
        }

        /* bloco informativo compacto */
        .muted-label {
            display: block;
            font-size: .75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin-bottom: .15rem;
        }

        /* padrão: links de favorito como texto normal */
        fav-client,
        fav-date {
            color: inherit;
            text-decoration: none;
            font-weight: 400;
        }

        /* destaque CLIENTE (amarelo) */
        .is-fav-client {
            background: #ffde59;
            color: #111;
            font-weight: 700 !important;
            font-size: 1.06em;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
@endsection

@section('js')
    <script>
        // CSRF para Ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        $(document).on('click', 'input#fav-client', function() {
            const $el = $('.fav-client');
            const url = $(this).data('url');

            const marcando = !$el.hasClass('is-fav-client');
            const msg = marcando ? 'Favoritar este cliente?' : 'Remover favorito deste cliente?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    // aplica no clicado
                    $el.toggleClass('is-fav-client', !!resp.is_favorite);
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar cliente', xhr.responseText);
                alert('Não foi possível alterar o favorito do cliente.');
            });
        });

        // Toggle favorito da DATA DE ENTREGA (com confirmação)
        $(document).on('click', 'a.fav-date', function(e) {
            e.preventDefault();
            var $el = $(this);
            var url = $el.data('url'); // rota /order-products/{id}/toggle-delivery-favorite
            var marcando = !$el.hasClass('is-fav-date');
            var msg = marcando ? 'Destacar esta DATA DE ENTREGA?' : 'Remover destaque desta DATA DE ENTREGA?';
            if (!confirm(msg)) return;

            $.post(url, {}, function(resp) {
                if (resp && resp.ok) {
                    $el.toggleClass('is-fav-date', !!resp.favorite_delivery);
                }
            }).fail(function(xhr) {
                console.error('Falha ao favoritar data de entrega', xhr.responseText);
                alert('Não foi possível alterar o destaque da data de entrega.');
            });
        });
    </script>
@endsection
