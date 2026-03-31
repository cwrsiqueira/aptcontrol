@extends('layouts.template')

@section('title', 'Produção pendente')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">

        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 gap-2">
            <h2 class="mb-0">Produção pendente</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-outline-danger" href="{{ route('reports.producao_pendente_pdf') }}" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf"></i> Gerar PDF
                </a>
                <a class="btn btn-sm btn-secondary" href="{{ route('reports.index') }}">Voltar aos relatórios</a>
            </div>
        </div>

        <p class="text-muted">
            Produtos com <strong>saldo a produzir</strong> maior que zero — mesma lógica da listagem em <em>Produtos</em>
            (falta entregar vs. estoque atual). Ordenados pela <strong>próxima entrega</strong> (mais próxima primeiro).
        </p>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table mb-0" style="text-align:center">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Produto</th>
                            <th>Estoque Atualizado</th>
                            <th>Produção estimada diária</th>
                            <th>Falta entregar</th>
                            <th>Produzir</th>
                            <th>Próxima entrega</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td class="text-left">
                                    @php
                                        $fullName = (string) $item->name;
                                        $shortName = \Illuminate\Support\Str::limit($fullName, 40, '...');
                                    @endphp
                                    <a href="{{ route('products.show', $item) }}" title="{{ $fullName }}">
                                        <span class="product-name">{{ $shortName }}</span>
                                    </a>
                                </td>
                                <td>{{ $item->current_stock ? number_format($item->current_stock, 0, '', '.') : 0 }}</td>
                                <td>{{ number_format($item->daily_production_forecast, 0, '', '.') }}</td>
                                <td>{{ number_format($item->quant_total, 0, '', '.') }}</td>
                                <td class="font-weight-bold">{{ number_format($item->produzir, 0, '', '.') }}</td>
                                <td>
                                    {{ $item->delivery_in ? \Carbon\Carbon::parse($item->delivery_in)->format('d/m/Y') : '--/--/----' }}
                                </td>
                                <td>
                                    @if (in_array('products.stock', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-secondary" title="Estoque"
                                            href="{{ route('products.stocks.index', $item->id) }}"><i class="fas fa-boxes"></i></a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Solicitar Acesso"><i
                                                class="fas fa-boxes"></i></button>
                                    @endif

                                    @if (in_array('products.cc', $user_permissions) || Auth::user()->is_admin)
                                        <a class="btn btn-sm btn-outline-success" title="Ver entregas"
                                            href="{{ route('cc_product', $item->id) }}"><i class="fas fa-truck"></i></a>
                                    @else
                                        <button class="btn btn-sm btn-outline-success" disabled title="Solicitar Acesso"><i
                                                class="fas fa-truck"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted text-center py-4">
                                    Nenhum produto com saldo a produzir no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->count())
                <div class="card-footer text-muted small">
                    Total: <strong>{{ $rows->count() }}</strong> produto(s).
                </div>
            @endif
        </div>
    </main>
@endsection
