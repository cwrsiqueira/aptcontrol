@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <div class="container">
        {{-- Cabeçalho --}}
        <div class="row mb-3">
            <div class="col">
                <h4 class="mb-0">Dashboard</h4>
                <small class="text-muted">Visão geral das entregas</small>
            </div>
        </div>

        {{-- Cards de status --}}
        <div class="row">
            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card border-danger h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <span class="badge badge-danger">Atrasadas</span>
                            </div>
                            <h3 class="mb-0">{{ number_format($cards['atrasadas'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <span class="badge badge-warning">Para hoje</span>
                            </div>
                            <h3 class="mb-0">{{ number_format($cards['hoje'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card border-info h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <span class="badge badge-info">Pendentes</span>
                            </div>
                            <h3 class="mb-0">{{ number_format($cards['pendentes'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <span class="badge badge-success">Concluídas</span>
                            </div>
                            <h3 class="mb-0">{{ number_format($cards['concluidas'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card border-secondary h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <span class="badge badge-secondary">Canceladas</span>
                            </div>
                            <h3 class="mb-0">{{ number_format($cards['canceladas'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
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
                        <p class="mb-1">Versão: <span
                                class="font-weight-bold">{{ $systemInfo['version'] ?? 'v0.0.0' }}</span></p>
                        <small class="text-muted">Atualizado em {{ $systemInfo['updated_at'] ?? '-' }}</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <strong>Últimas atualizações</strong>
                    </div>
                    <div class="card-body">
                        @if (!empty($systemInfo['updates']))
                            <ul class="mb-0">
                                @foreach ($systemInfo['updates'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">Sem atualizações recentes.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
