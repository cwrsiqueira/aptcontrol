@extends('layouts.template')

@section('title', 'Relatórios')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-4">
        <h2>Relatórios</h2>
        <p class="text-muted">Selecione um relatório para abrir.</p>

        <div class="card bg-light">
            <div class="card-body">
                <div class="list-group">

                    <a href="{{ route('reports.delivery_form') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-truck mr-2"></i>
                        Relatório de Entregas
                    </a>

                    <a href="{{ route('reports.producao_pendente') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-industry mr-2"></i>
                        Produção pendente (saldo a produzir)
                    </a>

                    <a href="{{ route('reports.stock_audit') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-boxes mr-2"></i>
                        Auditoria de Estoque
                    </a>

                </div>
            </div>
        </div>
    </main>
@endsection
