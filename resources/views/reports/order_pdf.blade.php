@php
    $isCif = strtolower($order->withdraw) === 'entregar';
@endphp
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Pedido #{{ $order->order_number }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 24px;
        }

        h1,
        h2,
        h3 {
            margin: 0;
        }

        .small {
            font-size: 11px;
        }

        .muted {
            color: #555;
        }

        .header {
            width: 100%;
            margin-bottom: 16px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .header-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .header-grid td {
            vertical-align: top;
        }

        .brand {
            font-weight: 700;
            font-size: 16px;
            letter-spacing: .5px;
        }

        .doc-title {
            font-size: 18px;
            margin-top: 6px;
            text-align: right;
        }

        .meta {
            width: 100%;
            margin: 10px 0 16px 0;
            border-collapse: collapse;
        }

        .meta td {
            padding: 6px 8px;
            border: 1px solid #000;
        }

        .meta .label {
            width: 22%;
            background: #f4f4f4;
            font-weight: 600;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }

        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        table.items th {
            text-align: left;
            font-weight: 700;
        }

        table.items td.num {
            text-align: right;
        }

        .section-title {
            font-weight: 700;
            margin: 16px 0 6px;
            text-transform: uppercase;
            font-size: 12px;
        }

        .spacer {
            height: 6px;
        }

        .footer {
            margin-top: 16px;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }

        @page {
            margin: 24px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table class="header-grid">
            <tr>
                <td style="width:65%;">
                    <table style="border-collapse:collapse;">
                        <tr>
                            <td style="width:68px;">
                                <img src="{{ public_path('logo.png') }}" alt="Logo" style="height:48px;">
                            </td>
                            <td>
                                <div class="brand">PSDControl — Controle de Entregas</div>
                                <div class="small">Relatório / Nota de Pedido gerado pelo sistema</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:35%;">
                    <div class="doc-title">Pedido #{{ $order->order_number }}</div>
                    <div class="small" style="text-align:right;">Emissão: {{ date('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Situação</td>
            <td>{{ $status }}</td>
        </tr>
        <tr>
            <td class="label">Data do Pedido</td>
            <td>{{ date('d/m/Y', strtotime($order->order_date)) }}</td>
        </tr>
        <tr>
            <td class="label">Cliente</td>
            <td>{{ optional($order->client)->name }}</td>
        </tr>
        <tr>
            <td class="label">Vendedor</td>
            <td>{{ optional($order->seller)->name }}</td>
        </tr>
        <tr>
            <td class="label">Entrega</td>
            <td>{{ ucfirst(strtolower($order->withdraw)) }} ({{ $isCif ? 'CIF' : 'FOB' }})</td>
        </tr>
        <tr>
            <td class="label">Pagamento</td>
            <td>{{ $order->payment }}</td>
        </tr>
    </table>

    <div class="section-title">Itens do Pedido</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Produto</th>
                <th style="width:90px;" class="num">Quantidade</th>
                <th style="width:95px;" class="num">Entrega</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order_products as $item)
                @if ($item->quant > 0)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td class="num">{{ number_format($item->quant, 0, '', '.') }}</td>
                        <td class="num">
                            {{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}
                            @if ($item->favorite_delivery)
                                • fixada
                            @endif
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" class="muted">Nenhum item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($saldo_produtos) && count($saldo_produtos))
        <div class="section-title">Saldos por Produto</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th style="width:100px;" class="num">Inicial</th>
                    <th style="width:100px;" class="num">Entregue</th>
                    <th style="width:100px;" class="num">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($saldo_produtos as $s)
                    <tr>
                        <td>{{ $s->product->name }}</td>
                        <td class="num">{{ number_format($s->saldo_inicial, 0, '', '.') }}</td>
                        <td class="num">{{ number_format($s->saldo_inicial - $s->saldo, 0, '', '.') }}</td>
                        <td class="num">{{ number_format($s->saldo, 0, '', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Observações</div>
    <div class="spacer"></div>
    <div class="muted">—</div>

    <div class="footer">
        Documento gerado automaticamente pelo sistema em {{ date('d/m/Y H:i') }}.
    </div>
</body>

</html>
