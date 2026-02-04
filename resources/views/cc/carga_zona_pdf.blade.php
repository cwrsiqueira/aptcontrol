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
        }

        th {
            background: #eee;
        }

        h2,
        h4 {
            margin: 0;
        }
    </style>
</head>

<body>

    <h2>CARGA – ZONA {{ strtoupper($zona) }}</h2>
    <h4>Gerado em: {{ $data }}</h4>

    <p>
        <strong>Total de produtos:</strong>
        {{ number_format($totalProdutos, 0, ',', '.') }}
    </p>

    <p>
        <strong>Paletes:</strong>
        @foreach ($resumoPaletes as $cap => $qt)
            {{ $qt }}x{{ $cap }}@if (!$loop->last)
                ,
            @endif
        @endforeach
    </p>

    <table>
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Produto</th>
                <th>Quant</th>
                <th>Paletes</th>
                <th>Endereço</th>
                <th>Bairro</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i)
                <tr>
                    <td>{{ $i->order_number }}</td>
                    <td>{{ $i->client_name }}</td>
                    <td>{{ $i->client_phone }}</td>
                    <td>{{ $i->product_name }}</td>
                    <td>{{ (int) $i->quant }}</td>
                    <td>{{ implode(', ', $i->paletes) }}</td>
                    <td>{{ $i->endereco }}</td>
                    <td>{{ $i->bairro }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
