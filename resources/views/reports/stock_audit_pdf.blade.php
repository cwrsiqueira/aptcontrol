<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Auditoria de Estoque</title>
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

        @page {
            margin: 10mm;
        }
    </style>
</head>

<body>

    <h1>Auditoria de Estoque</h1>
    <div class="muted">Gerado em: {{ date('d/m/Y H:i') }}</div>

    <div class="box">
        <strong>Período:</strong> {{ date('d/m/Y', strtotime($from)) }} a {{ date('d/m/Y', strtotime($to)) }}
        &nbsp; | &nbsp;
        <strong>Só divergências:</strong> {{ $only_divergent ? 'Sim' : 'Não' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th class="num">Abertura</th>
                <th class="num">Entregue</th>
                <th class="num">Fechamento</th>
                <th class="num">Fech. Esperado</th>
                <th class="num">Ajuste</th>
                <th class="num">Previsão</th>
                <th class="num">Esperado D+1</th>
                <th class="num">Abertura D+1</th>
                <th class="num">Divergência</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                @php
                    $hasNext = $r->next_open_stock !== null;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->day)->format('d/m/Y') }}</td>
                    <td>{{ $r->product_name }}</td>
                    <td class="num">{{ number_format((int) $r->open_stock, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->delivered_qty, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->close_stock, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->expected_close_no_adjust, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->implied_adjustment, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->forecast, 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $r->expected_next_open, 0, '', '.') }}</td>
                    <td class="num">{{ $hasNext ? number_format((int) $r->next_open_stock, 0, '', '.') : '—' }}</td>
                    <td class="num">{{ $hasNext ? number_format((int) $r->divergence_next_day, 0, '', '.') : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="muted">Nenhum registro.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
