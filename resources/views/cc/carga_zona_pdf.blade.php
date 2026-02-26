<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Carga Zona {{ $zona }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        th {
            background: #eee;
        }

        h2 {
            margin-bottom: 5px;
        }

        .sub {
            font-size: 10px;
        }
    </style>
</head>

<body>

    <h2>CARGA – ZONA {{ strtoupper($zona) }}</h2>
    <p>Gerado em: {{ $data }}</p>

    <p>
        <strong>Resumo da carga ({{ $totalPaletes }} paletes)</strong><br>
        Total de produtos: {{ number_format($totalProdutos, 0, ',', '.') }}
    </p>

    @foreach ($resumoProdutos as $produto => $dados)
        <p class="sub">
            - {{ $produto }}:
            {{ number_format($dados['produtos'], 0, ',', '.') }} produtos |
            @foreach ($dados['paletes'] as $cap => $qt)
                {{ $qt }}x{{ $cap }}@if (!$loop->last)
                    ,
                @endif
            @endforeach
        </p>
    @endforeach

    <table>
        <thead>
            <tr>
                <th style="width:90px;">Pedido</th>
                <th>Produto</th>
                <th style="width:80px;">Quant</th>
                <th style="width:120px;">Paletes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i)
                {{-- Linha principal --}}
                <tr>
                    <td>{{ $i->order_number }}</td>
                    <td>{{ $i->product_name }}</td>
                    <td>{{ (int) $i->quant }}</td>
                    <td>{{ implode(', ', $i->paletes) }}</td>
                </tr>

                {{-- Linha secundária (endereçamento / contato) --}}
                <tr>
                    <td colspan="4" class="sub">
                        <strong>Cliente:</strong> {{ $i->client_name }}
                        @if ($i->client_phone)
                            | <strong>Tel:</strong> {{ $i->client_phone }}
                        @endif
                        <br>
                        <strong>Endereço:</strong> {{ $i->endereco }}
                        | <strong>Bairro:</strong> {{ $i->bairro }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
