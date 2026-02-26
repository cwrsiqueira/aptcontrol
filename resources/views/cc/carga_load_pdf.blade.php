<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Carga {{ $load->motorista ?? $truck->responsavel }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: top; }
        th { background: #eee; }
        h2 { margin-bottom: 5px; }
        .sub { font-size: 10px; }
        .zona-header { background: #f5f5f5; font-weight: bold; padding: 6px; margin-top: 12px; }
    </style>
</head>

<body>

    <h2>CARGA – {{ strtoupper($load->motorista ?? $truck->responsavel) }}</h2>
    <p><strong>Caminhão:</strong> {{ $truck->modelo ?? '—' }} | <strong>Placa:</strong> {{ $truck->placa ?? '—' }} | <strong>Capacidade:</strong> {{ $truck->capacidade_paletes }} paletes</p>
    <p>Gerado em: {{ $data }}</p>

    <p>
        <strong>Resumo da carga ({{ $totalPaletes }} paletes)</strong><br>
        Total de produtos: {{ number_format($totalProdutos, 0, ',', '.') }}
    </p>

    @foreach ($resumoProdutos as $produto => $dados)
        <p class="sub">
            - {{ $produto }}:
            {{ number_format($dados['produtos'], 0, ',', '.') }} produtos |
            {{ $dados['paletes'] }} paletes
        </p>
    @endforeach

    @foreach ($itemsPorZona as $zonaNome => $itensZona)
        <div class="zona-header">ZONA {{ strtoupper($zonaNome ?: 'SEM ZONA') }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:90px;">Pedido</th>
                    <th>Produto</th>
                    <th style="width:80px;">Quant</th>
                    <th style="width:80px;">Paletes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itensZona as $li)
                    @php $op = $li->orderProduct; @endphp
                    <tr>
                        <td>{{ $op->order->order_number ?? '' }}</td>
                        <td>{{ $op->product->name ?? '' }}</td>
                        <td>{{ (int) $op->quant }}</td>
                        <td>{{ $li->qtd_paletes }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="sub">
                            <strong>Cliente:</strong> {{ $op->order->client->name ?? 'não cadastrado' }}
                            | <strong>Tel:</strong> {{ $op->order->client->contact ?? 'não cadastrado' }}
                            <br>
                            <strong>Endereço:</strong> {{ $op->order->endereco ?? 'não cadastrado' }}
                            | <strong>Bairro:</strong> {{ $op->order->bairro ?? $li->bairro ?? 'não cadastrado' }}
                            | <strong>Zona:</strong> {{ ($li->zone_id ? ($li->zone->nome ?? '') : ($li->zona_nome ?? '')) ?: 'não cadastrado' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>

</html>
