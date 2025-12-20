@extends('layouts.template')

@section('title', 'Dashboard')

@section('content')
    <div class="container">
        {{-- Cabeçalho --}}
        <div class="row mb-3">
            <div class="col">
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">Visão geral das entregas</small>
            </div>
        </div>

        {{-- Mostra errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Cards de status (operacional) --}}
        <div class="row">
            <div class="col-md-4 col-xl-3 mb-3">
                <div class="card border-danger h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="mr-3">
                                <span class="badge badge-danger">Atrasadas</span>
                            </div>
                            <div class="card-number">
                                {{ number_format($cards['atrasadas'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Entregas com data menor que hoje.</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-3 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="mr-3">
                                <span class="badge badge-warning">Para hoje</span>
                            </div>
                            <div class="card-number">
                                {{ number_format($cards['hoje'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Entregas previstas para hoje.</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-3 mb-3">
                <div class="card border-info h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="mr-3">
                                <span class="badge badge-info">Pendentes</span>
                            </div>
                            <div class="card-number">
                                {{ number_format($cards['pendentes'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Pedidos em aberto com saldo pendente.</small>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-3 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="mr-3">
                                <span class="badge badge-primary">Pendentes p/ amanhã</span>
                            </div>
                            <div class="card-number">
                                {{ number_format($cards['amanha'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Ajuda a planejar expedição.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Versão e últimas atualizações --}}
        <div class="row mt-3">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Versão do sistema</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            Versão:
                            <span class="font-weight-bold">
                                {{ $systemInfo['version'] ?? 'v1.3.0' }}
                            </span>
                        </p>
                        <small class="text-muted">
                            Atualizado em {{ $systemInfo['updated_at'] ?? date('d/m/Y') }}
                        </small>

                        <hr class="my-3">

                        <small class="text-muted d-block">
                            Obs.: o estoque agora é controlado exclusivamente pelo módulo de estoque (auditoria ativa).
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Últimas atualizações</strong>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>CRUD de Permissões (permission_items) com slug protegido para evitar quebra de acessos.</li>
                            <li>Módulo de Estoque por produto (lançamentos + atualização automática do current_stock).</li>
                            <li>Auditoria de estoque (estoque lançado × entregas × previsão diária).</li>
                            <li>Hub de Relatórios (Relatório de Entregas + Auditoria) com exportação CSV.</li>
                            <li>Impressão em PDF (Pedido, Relatório de Entregas e Auditoria de Estoque).</li>
                            <li>Produtos agora listam primeiro quem tem saldo disponível e mostram previsão de entrega.</li>
                            <li>Estoque removido do cadastro/edição de produto para manter histórico e auditoria.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('css')
    <style>
        /* Evita estourar com números grandes e mantém o card alinhado */
        .card-number {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 55%;
            text-align: right;
        }
    </style>
@endsection
