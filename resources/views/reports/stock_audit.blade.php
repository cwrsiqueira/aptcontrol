@extends('layouts.template')

@section('title', 'Auditoria de Estoque')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-4">

        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <div>
                <h2 class="mb-0">Auditoria de Estoque</h2>
                <small class="text-muted">Confronto diário: estoque lançado × entregas × previsão</small>
            </div>
            <a href="{{ route('reports.stock_audit_pdf', request()->all()) }}" target="_blank" rel="noopener"
                class="btn btn-sm btn-outline-secondary mr-2">
                Gerar PDF
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.stock_audit') }}" class="form-row align-items-end">
                    <div class="col-md-2 mb-2">
                        <label>De</label>
                        <input type="date" name="from" class="form-control" value="{{ $from }}">
                    </div>

                    <div class="col-md-2 mb-2">
                        <label>Até</label>
                        <input type="date" name="to" class="form-control" value="{{ $to }}">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label>Produto</label>
                        <select name="product_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" @if ((string) $product_id === (string) $p->id) selected @endif>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label>Por página</label>
                        <select name="per_page" class="form-control">
                            @foreach ([10, 20, 30, 50, 100] as $pp)
                                <option value="{{ $pp }}" @if ((int) $per_page === (int) $pp) selected @endif>
                                    {{ $pp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="only_divergent" id="only_divergent"
                                value="1" @if ($only_divergent) checked @endif>
                            <label class="form-check-label" for="only_divergent">
                                Só divergências
                            </label>
                        </div>

                        <button class="btn btn-primary btn-block mt-2" type="submit">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Como ler:</strong>
            Abertura (S₀) = primeiro estoque do dia • Fechamento (S₁) = último estoque do dia •
            Entregue (E) = somatório das entregas do dia •
            <br>
            <strong>Ajuste implícito</strong> = S₁ − (S₀ − E) •
            <strong>Divergência D→D+1</strong> = Abertura do dia seguinte − (S₁ + previsão).
        </div>

        <div class="card bg-light">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="text-align:center">
                    <thead class="thead-light">
                        <tr>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Abertura (S₀)</th>
                            <th>Entregue (E)</th>
                            <th>Fechamento (S₁)</th>
                            <th>Fech. Esperado</th>
                            <th>Ajuste Implícito</th>
                            <th>Previsão</th>
                            <th>Esperado D+1</th>
                            <th>Abertura D+1</th>
                            <th>Divergência D→D+1</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php
                                $div = $r->divergence_next_day;
                                $adj = $r->implied_adjustment;
                                $hasNext = $r->next_open_stock !== null;
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($r->day)->format('d/m/Y') }}</td>
                                <td class="text-left">{{ $r->product_name }}</td>
                                <td>{{ number_format((int) $r->open_stock, 0, '', '.') }}</td>
                                <td>{{ number_format((int) $r->delivered_qty, 0, '', '.') }}</td>
                                <td>{{ number_format((int) $r->close_stock, 0, '', '.') }}</td>
                                <td>{{ number_format((int) $r->expected_close_no_adjust, 0, '', '.') }}</td>

                                <td class="@if ((int) $adj !== 0) text-danger font-weight-bold @endif">
                                    {{ number_format((int) $adj, 0, '', '.') }}
                                </td>

                                <td>{{ number_format((int) $r->forecast, 0, '', '.') }}</td>
                                <td>{{ number_format((int) $r->expected_next_open, 0, '', '.') }}</td>

                                <td>
                                    @if ($hasNext)
                                        {{ number_format((int) $r->next_open_stock, 0, '', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="@if ($hasNext && (int) $div !== 0) text-danger font-weight-bold @endif">
                                    @if ($hasNext)
                                        {{ number_format((int) $div, 0, '', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-muted">Nenhum registro no período/filtro.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $rows->links() }}
            </div>
        </div>

    </main>
@endsection
