<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Produção pendente</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 16px; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 5px 6px; text-align: center; }
        th { background: #eee; font-weight: 700; }
        td.prod { text-align: left; }
        td.num { text-align: right; }
        tfoot td { font-weight: 700; background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Produção pendente (saldo a produzir)</h1>
    <p class="muted">Gerado em {{ $geradoEm }} — {{ $total }} produto(s)</p>

    <table>
        <thead>
            <tr>
                <th style="width:40px;">ID</th>
                <th>Produto</th>
                <th style="width:75px;">Estoque</th>
                <th style="width:55px;">Produção/dia</th>
                <th style="width:75px;">Falta entregar</th>
                <th style="width:75px;">Produzir</th>
                <th style="width:75px;">Próxima entrega</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td class="prod">{{ $item->name }}</td>
                    <td class="num">{{ number_format((int) ($item->current_stock ?? 0), 0, '', '.') }}</td>
                    <td class="num">{{ number_format((int) $item->daily_production_forecast, 0, '', '.') }}</td>
                    <td class="num">{{ number_format($item->quant_total, 0, '', '.') }}</td>
                    <td class="num">{{ number_format($item->produzir, 0, '', '.') }}</td>
                    <td>
                        {{ $item->delivery_in ? \Carbon\Carbon::parse($item->delivery_in)->format('d/m/Y') : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align:center;">Nenhum produto com saldo a produzir.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows->count())
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">Total a produzir (soma)</td>
                    <td class="num">{{ number_format($rows->sum('produzir'), 0, '', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
