@extends('layouts.template')

@section('title', 'Relatórios')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Relatórios</h2>
            <div>
                {{-- limpa os filtros (sem query string) --}}
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.delivery_form') }}">Limpar filtros</a>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
            </div>
        </div>

        <form action="{{ route('report_delivery') }}" method="get">
            <div class="card card-lift">
                <div class="card-header">
                    <h4 class="mb-0">Gerador de Relatórios de Entregas</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Produtos (multi) --}}
                        <div class="form-group col-md-4">
                            <label class="mb-1">Produtos</label>
                            <select name="products[]" class="form-control" multiple size="10">
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}"
                                        {{ in_array($p->id, $product_ids ?? []) ? 'selected' : '' }}>{{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Deixe vazio para incluir todos os produtos.</small>
                        </div>

                        <div class="col-md-8">
                            <div class="row">
                                {{-- Cliente (datalist) --}}
                                <div class="form-group col-md-6">
                                    <label for="cliente" class="mb-1">Cliente</label>
                                    <input type="search" name="cliente" id="cliente" list="lista-clientes"
                                        value="{{ $cliente }}" class="form-control" autocomplete="off"
                                        placeholder="Digite o nome do cliente">
                                    <datalist id="lista-clientes">
                                        @foreach ($clients as $c)
                                            <option value="{{ $c }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted">Nome único.</small>
                                </div>

                                {{-- Vendedor (datalist) --}}
                                <div class="form-group col-md-6">
                                    <label for="vendedor" class="mb-1">Vendedor</label>
                                    <input type="search" name="vendedor" id="vendedor" list="lista-vendedores"
                                        value="{{ $vendedor }}" class="form-control" autocomplete="off"
                                        placeholder="Digite o nome do vendedor">
                                    <datalist id="lista-vendedores">
                                        @foreach ($sellers as $s)
                                            <option value="{{ $s }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted">Nome único.</small>
                                </div>

                                {{-- Pedido (datalist) --}}
                                <div class="form-group col-md-6">
                                    <label for="pedido" class="mb-1">Pedido</label>
                                    <input type="search" name="pedido" id="pedido" list="lista-pedidos"
                                        value="{{ $pedido }}" class="form-control" autocomplete="off"
                                        placeholder="Digite o número do pedido">
                                    <datalist id="lista-pedidos">
                                        @foreach ($orders as $o)
                                            <option value="{{ $o }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted">Número único.</small>
                                </div>

                                {{-- Tipo de entrega (radios) --}}
                                <div class="form-group col-md-6">
                                    <label class="mb-1 d-block">Tipo de entrega</label>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="w_todas" name="withdraw" value="todas"
                                            {{ $withdraw === 'todas' ? 'checked' : '' }} class="custom-control-input"
                                            checked>
                                        <label class="custom-control-label" for="w_todas">Todas</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="w_entregar" name="withdraw" value="entregar"
                                            {{ $withdraw === 'entregar' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="w_entregar">Entregar (CIF)</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="w_retirar" name="withdraw" value="retirar"
                                            {{ $withdraw === 'retirar' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="w_retirar">Retirar (FOB)</label>
                                    </div>
                                </div>

                                {{-- Campo de data (radios) --}}
                                <div class="form-group col-md-6">
                                    <label class="mb-1 d-block">Filtrar por</label>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="d_delivery" name="date_field" value="delivery"
                                            {{ $date_field === 'delivery' ? 'checked' : '' }} class="custom-control-input"
                                            checked>
                                        <label class="custom-control-label" for="d_delivery">Data de entrega</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="d_order" name="date_field" value="order"
                                            {{ $date_field === 'order' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="d_order">Data do pedido</label>
                                    </div>
                                </div>

                                {{-- Status (radios) --}}
                                <div class="form-group col-md-6">
                                    <label class="mb-1 d-block">Status da entrega</label>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_pend" name="status" value="pendentes"
                                            {{ $status === 'pendentes' ? 'checked' : '' }} class="custom-control-input"
                                            checked>
                                        <label class="custom-control-label" for="s_pend">Pendentes</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_real" name="status" value="realizadas"
                                            {{ $status === 'realizadas' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_real">Realizadas</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_both" name="status" value="ambos"
                                            {{ $status === 'ambos' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_both">Ambos</label>
                                    </div>
                                </div>

                                {{-- Período --}}
                                <div class="form-group col-md-3">
                                    <label class="mb-1">Data inicial</label>
                                    <input class="form-control" type="date" name="date_ini"
                                        value="{{ $date_ini }}">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="mb-1">Data final</label>
                                    <input class="form-control" type="date" name="date_fin"
                                        value="{{ $date_fin }}">
                                </div>

                                {{-- Status (radios) --}}
                                <div class="form-group col-md-6">
                                    <label class="mb-1 d-block">Pagamento</label>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_todos" name="payment" value="" checked
                                            {{ $payment === '' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_todos">Todos</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_aberto" name="payment" value="Aberto"
                                            {{ $payment === 'Aberto' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_aberto">Aberto</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_parcial" name="payment" value="Parcial"
                                            {{ $payment === 'Parcial' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_parcial">Parcial</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="s_total" name="payment" value="Total"
                                            {{ $payment === 'Total' ? 'checked' : '' }} class="custom-control-input">
                                        <label class="custom-control-label" for="s_total">Total</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-12 mt-2">
                                    <button class="btn btn-secondary"><i class="fas fa-search mr-1"></i> Gerar
                                        relatório</button>
                                    <a class="btn btn-outline-secondary ml-2" href="{{ route('reports.index') }}">Limpar
                                        filtros</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Os <strong>saldos</strong> de pendências são calculados sobre toda a base e depois filtrados por
                        período, garantindo valores reais
                        mesmo quando houver lançamentos fora do intervalo.
                    </small>
                </div>
            </div>
        </form>
    </main>
@endsection

@section('css')
    <style>
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .page-header h2 {
            font-weight: 600;
        }
    </style>
@endsection
