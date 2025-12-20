@extends('layouts.estilos')

@section('title', 'Relatório de Entregas')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Relatório de Entregas</h2>
            <div class="btn-group">
                @php
                    // preserva todos os filtros atuais (exceto export=csv)
                    $backQs = request()->except('export');
                @endphp

                <a class="btn btn-sm btn-secondary" href="{{ route('reports.delivery_form', $backQs) }}" id="btn_voltar">&lt;
                    Voltar</a>
                @php
                    $qs = request()->all();
                    $qs['export'] = 'csv';
                @endphp
                <a class="btn btn-sm btn-outline-primary" href="{{ route('report_delivery', $qs) }}">Baixar CSV</a>
                <button class="btn btn-sm btn-secondary" id="btn_print">Imprimir</button>
                @php $qsPdf = request()->all(); @endphp
                <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"
                    href="{{ route('report_delivery_pdf', $qsPdf) }}">
                    Gerar PDF
                </a>
            </div>
        </div>

        {{-- Resumo dos filtros --}}
        <div class="card card-lift mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2"><strong>Status:</strong><br>
                        {{ $meta['status'] === 'ambos' ? 'Pendentes e Realizadas' : ucfirst($meta['status']) }}
                    </div>
                    <div class="col-md-3"><strong>Período:</strong><br>
                        @if ($meta['date_ini'] || $meta['date_fin'])
                            {{ $meta['date_ini'] ? date('d/m/Y', strtotime($meta['date_ini'])) : '—' }}
                            a
                            {{ $meta['date_fin'] ? date('d/m/Y', strtotime($meta['date_fin'])) : '—' }}
                        @else
                            (sem filtro)
                        @endif
                    </div>
                    <div class="col-md-2"><strong>Campo:</strong><br>
                        {{ $meta['date_field'] === 'order' ? 'Data do pedido' : 'Data de entrega' }}
                    </div>
                    <div class="col-md-2"><strong>Entrega:</strong><br>
                        @php $w = strtolower($meta['withdraw'] ?? 'todas'); @endphp
                        {{ $w === 'todas' ? 'Todas' : ($w === 'retirar' ? 'Retirar (FOB)' : 'Entregar (CIF)') }}
                    </div>
                    <div class="col-md-3">
                        <strong>Produtos:</strong><br>
                        @if (!empty($products) && $products->count())
                            @foreach ($products as $p)
                                <span class="badge badge-mute">{{ $p->name }}</span>
                            @endforeach
                        @else
                            Todos
                        @endif
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3"><strong>Cliente:</strong> {{ $meta['cliente'] ?? 'Todos' }}</div>
                    <div class="col-md-3"><strong>Vendedor:</strong> {{ $meta['vendedor'] ?? 'Todos' }}</div>
                    <div class="col-md-3"><strong>Pedido:</strong> {{ $meta['pedido'] ?? 'Todos' }}</div>
                    <div class="col-md-3"><strong>Pagamento:</strong> {{ $meta['payment'] ?? 'Todos' }}</div>
                </div>

                <hr class="my-3">

                <div class="row">
                    @if (in_array($meta['status'], ['pendentes', 'ambos']))
                        <div class="col-md-6"><strong>Total pendente:</strong>
                            {{ number_format($meta['total_pendentes'] ?? 0, 0, '', '.') }}</div>
                    @endif
                    @if (in_array($meta['status'], ['realizadas', 'ambos']))
                        <div class="col-md-6"><strong>Total realizadas:</strong>
                            {{ number_format($meta['total_realizadas'] ?? 0, 0, '', '.') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabela Pendentes --}}
        @if (in_array($meta['status'], ['pendentes', 'ambos']))
            <div class="card card-lift mb-4">
                <div class="card-header"><strong>Pendentes</strong></div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-light sticky-header">
                            <tr>
                                <th>Data pedido</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Contato</th>
                                <th>Categoria</th>
                                <th>Produto</th>
                                <th class="text-right">Quantidade</th>
                                <th>Data entrega</th>
                                <th>Vendedor</th>
                                <th>Tipo de entrega</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingData as $item)
                                @php
                                    $order = $item->order ?? null;
                                    $client = $order->client ?? null;
                                    $seller = $order->seller ?? null;
                                    $prod = $item->product ?? null;
                                    $qtd = $item->saldo > 0 ? min((int) $item->saldo, (int) $item->quant) : 0;
                                    if ($qtd <= 0) {
                                        continue;
                                    }
                                    $isLate = $item->delivery_date && $item->delivery_date < date('Y-m-d');
                                @endphp
                                <tr class="{{ $isLate ? 'row-late' : '' }}">
                                    <td>{{ $order ? date('d/m/Y', strtotime($order->order_date)) : '—' }}</td>
                                    <td>
                                        @if ($order)
                                            <a
                                                href="{{ route('order_products.index', ['order' => $order->id]) }}">#{{ $item->order_id }}</a>
                                        @else
                                            #{{ $item->order_id }}
                                        @endif
                                    </td>
                                    <td>{{ $client->name ?? '—' }}</td>
                                    <td>{{ $client->contact ?? '—' }}</td>
                                    <td>{{ $client->category->name ?? '—' }}</td>
                                    <td>{{ $prod->name ?? '#' . $item->product_id }}</td>
                                    <td class="text-right">{{ number_format($qtd, 0, '', '.') }}</td>
                                    <td>{{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}
                                    </td>
                                    <td>{{ $seller->name ?? '—' }}</td>
                                    @php $isCif = isset($order->withdraw) ? (strtolower($order->withdraw) === 'entregar') : null; @endphp
                                    <td>{{ isset($order->withdraw) ? ucfirst($order->withdraw) . ' (' . ($isCif ? 'CIF' : 'FOB') . ')' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Nenhum item pendente nos filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Tabela Realizadas --}}
        @if (in_array($meta['status'], ['realizadas', 'ambos']))
            <div class="card card-lift mb-5">
                <div class="card-header"><strong>Realizadas</strong></div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="thead-light sticky-header">
                            <tr>
                                <th>Data pedido</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Contato</th>
                                <th>Categoria</th>
                                <th>Produto</th>
                                <th class="text-right">Entregue</th>
                                <th>Data entrega</th>
                                <th>Vendedor</th>
                                <th>Tipo de entrega</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deliveredData as $item)
                                @php
                                    $order = $item->order ?? null;
                                    $client = $order->client ?? null;
                                    $seller = $order->seller ?? null;
                                    $prod = $item->product ?? null;
                                    $qtd = (int) ($item->delivered_qty ?? 0);
                                    if ($qtd <= 0) {
                                        continue;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $order ? date('d/m/Y', strtotime($order->order_date)) : '—' }}</td>
                                    <td>
                                        @if ($order)
                                            <a
                                                href="{{ route('order_products.index', ['order' => $order->id]) }}">#{{ $item->order_id }}</a>
                                        @else
                                            #{{ $item->order_id }}
                                        @endif
                                    </td>
                                    <td>{{ $client->name ?? '—' }}</td>
                                    <td>{{ $client->contact ?? '—' }}</td>
                                    <td>{{ $client->category->name ?? '—' }}</td>
                                    <td>{{ $prod->name ?? '#' . $item->product_id }}</td>
                                    <td class="text-right">{{ number_format($qtd, 0, '', '.') }}</td>
                                    <td>{{ $item->created_at ? date('d/m/Y', strtotime($item->created_at)) : '—' }}
                                    </td>
                                    <td>{{ $seller->name ?? '—' }}</td>
                                    @php $isCif = isset($order->withdraw) ? (strtolower($order->withdraw) === 'entregar') : null; @endphp
                                    <td>{{ isset($order->withdraw) ? ucfirst($order->withdraw) . ' (' . ($isCif ? 'CIF' : 'FOB') . ')' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Nenhuma entrega realizada nos
                                        filtros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>
@endsection

@section('css')
    <style>
        /* layout normal */
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .page-header h2 {
            font-weight: 600;
        }

        .tableFixHead {
            max-height: 65vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        tbody tr:hover {
            background-color: #f6f9fc;
        }

        .row-late {
            color: #d9534f;
            font-weight: 700;
        }

        .badge-mute {
            background: #f1f3f5;
            color: #495057;
            padding: .25rem .5rem;
            border-radius: .375rem;
            font-weight: 600;
        }

        /* ------------ PRINT ------------- */
        @page {
            size: A4 landscape;
            /* usa Paisagem */
            margin: 8mm;
            /* margem apertada */
        }

        @media print {

            html,
            body {
                height: auto;
            }

            #btn_voltar,
            #btn_print,
            .btn-group a,
            .btn-group button {
                display: none !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            /* evita cortar a tabela */
            table.table {
                table-layout: fixed;
                /* distribui largura igualmente */
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                /* texto menor */
            }

            .table th,
            .table td {
                padding: 4px 6px !important;
                /* menos padding = mais conteúdo por página */
                vertical-align: top;
                word-break: break-word;
                /* quebra linhas longas */
                white-space: normal;
            }

            /* Repete cabeçalho em cada página impressa */
            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-row-group;
            }

            tr {
                page-break-inside: avoid;
            }

            /* Se ainda assim “estourar”, descomente para reduzir mais:
                                    body { zoom: 0.85; }  // Chrome costuma respeitar
                                    */
        }
    </style>
@endsection


@section('js')
    <script>
        document.getElementById('btn_print')?.addEventListener('click', function() {
            this.style.display = 'none';
            // document.getElementById('btn_voltar')?.style.display = 'none';
            window.print();
            location.reload();
        });
    </script>
@endsection
