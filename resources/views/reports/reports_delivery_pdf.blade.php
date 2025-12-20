<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Relatório de Entregas</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #111;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 6px;
        }

        .muted {
            color: #555;
        }

        .box {
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .num {
            text-align: right;
        }

        .section {
            margin-top: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        @page {
            margin: 10mm;
        }
    </style>
</head>

<body>

    <h1>Relatório de Entregas</h1>
    <div class="muted">Gerado em: {{ date('d/m/Y H:i') }}</div>

    <div class="box">
        <strong>Status:</strong>
        {{ $meta['status'] === 'ambos' ? 'Pendentes e Realizadas' : ucfirst($meta['status']) }}
        &nbsp; | &nbsp;
        <strong>Período:</strong>
        {{ $meta['date_ini'] ? date('d/m/Y', strtotime($meta['date_ini'])) : '—' }}
        a
        {{ $meta['date_fin'] ? date('d/m/Y', strtotime($meta['date_fin'])) : '—' }}
        &nbsp; | &nbsp;
        <strong>Campo:</strong> {{ $meta['date_field'] === 'order' ? 'Data do pedido' : 'Data de entrega' }}
        <br>
        <strong>Entrega:</strong>
        @php $w = strtolower($meta['withdraw'] ?? 'todas'); @endphp
        {{ $w === 'todas' ? 'Todas' : ($w === 'retirar' ? 'Retirar (FOB)' : 'Entregar (CIF)') }}
        &nbsp; | &nbsp;
        <strong>Pagamento:</strong> {{ $meta['payment'] ?? 'Todos' }}
        <br>
        <strong>Cliente:</strong> {{ $meta['cliente'] ?? 'Todos' }}
        &nbsp; | &nbsp;
        <strong>Vendedor:</strong> {{ $meta['vendedor'] ?? 'Todos' }}
        &nbsp; | &nbsp;
        <strong>Pedido:</strong> {{ $meta['pedido'] ?? 'Todos' }}
        <br>
        <strong>Produtos:</strong>
        @if (!empty($products) && $products->count())
            @foreach ($products as $p)
                {{ $p->name }}@if (!$loop->last)
                    ,
                @endif
            @endforeach
        @else
            Todos
        @endif
        <br>
        <strong>Total pendente:</strong> {{ number_format($meta['total_pendentes'] ?? 0, 0, '', '.') }}
        &nbsp; | &nbsp;
        <strong>Total realizadas:</strong> {{ number_format($meta['total_realizadas'] ?? 0, 0, '', '.') }}
    </div>

    @if (in_array($meta['status'], ['pendentes', 'ambos']))
        <div class="section">Pendentes</div>
        <table>
            <thead>
                <tr>
                    <th>Data pedido</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Contato</th>
                    <th>Categoria</th>
                    <th>Produto</th>
                    <th class="num">Quantidade</th>
                    <th>Data entrega</th>
                    <th>Vendedor</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendingData as $item)
                    @php
                        $order = $item->order ?? null;
                        $client = $order->client ?? null;
                        $seller = $order->seller ?? null;
                        $prod = $item->product ?? null;
                        $qtd = $item->saldo > 0 ? min((int) $item->saldo, (int) $item->quant) : 0;
                        if ($qtd <= 0) {
                            continue;
                        }
                        $isCif = isset($order->withdraw) ? strtolower($order->withdraw) === 'entregar' : null;
                    @endphp
                    <tr>
                        <td>{{ $order ? date('d/m/Y', strtotime($order->order_date)) : '—' }}</td>
                        <td>#{{ $item->order_id }}</td>
                        <td>{{ $client->name ?? '—' }}</td>
                        <td>{{ $client->contact ?? '—' }}</td>
                        <td>{{ $client->category->name ?? '—' }}</td>
                        <td>{{ $prod->name ?? '#' . $item->product_id }}</td>
                        <td class="num">{{ number_format($qtd, 0, '', '.') }}</td>
                        <td>{{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}</td>
                        <td>{{ $seller->name ?? '—' }}</td>
                        <td>{{ isset($order->withdraw) ? ucfirst($order->withdraw) . ' (' . ($isCif ? 'CIF' : 'FOB') . ')' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (in_array($meta['status'], ['realizadas', 'ambos']))
        <div class="section">Realizadas</div>
        <table>
            <thead>
                <tr>
                    <th>Data pedido</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Contato</th>
                    <th>Categoria</th>
                    <th>Produto</th>
                    <th class="num">Entregue</th>
                    <th>Data entrega</th>
                    <th>Vendedor</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveredData as $item)
                    @php
                        $order = $item->order ?? null;
                        $client = $order->client ?? null;
                        $seller = $order->seller ?? null;
                        $prod = $item->product ?? null;
                        $qtd = (int) ($item->delivered_qty ?? 0);
                        if ($qtd <= 0) {
                            continue;
                        }
                        $isCif = isset($order->withdraw) ? strtolower($order->withdraw) === 'entregar' : null;
                    @endphp
                    <tr>
                        <td>{{ $order ? date('d/m/Y', strtotime($order->order_date)) : '—' }}</td>
                        <td>#{{ $item->order_id }}</td>
                        <td>{{ $client->name ?? '—' }}</td>
                        <td>{{ $client->contact ?? '—' }}</td>
                        <td>{{ $client->category->name ?? '—' }}</td>
                        <td>{{ $prod->name ?? '#' . $item->product_id }}</td>
                        <td class="num">{{ number_format($qtd, 0, '', '.') }}</td>
                        <td>{{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}</td>
                        <td>{{ $seller->name ?? '—' }}</td>
                        <td>{{ isset($order->withdraw) ? ucfirst($order->withdraw) . ' (' . ($isCif ? 'CIF' : 'FOB') . ')' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>

</html>
