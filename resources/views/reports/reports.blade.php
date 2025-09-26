@extends('layouts.template')

@section('title', 'Relatórios')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Relatórios</h2>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card card-lift">
                    <div class="card-header">
                        <h4 class="mb-0">Relação de Entregas</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('report_delivery') }}" method="get">
                            <div class="form-group">
                                <label for="delivery_date" class="mb-1">Selecione a data</label>
                                <div class="input-group">
                                    <input class="form-control" type="date" name="delivery_date" id="delivery_date"
                                        value="{{ date('Y-m-d') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-secondary" type="submit">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Gera a lista de entregas para o dia selecionado.</small>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mantive o bloco por período comentado como estava no seu código --}}
            {{--
        <div class="col-md-6">
            <div class="card card-lift">
                <div class="card-header">
                    <h4 class="mb-0">Relação de Entregas por período</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('report_delivery_byPeriod') }}" method="get">
                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label for="date_ini" class="mb-1">Data inicial</label>
                                <input class="form-control" type="date" name="date_ini" id="date_ini" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="date_fin" class="mb-1">Data final</label>
                                <input class="form-control" type="date" name="date_fin" id="date_fin" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <button class="btn btn-secondary" type="submit">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                        <small class="text-muted d-block mt-2">Filtra as entregas entre as datas informadas.</small>
                    </form>
                </div>
            </div>
        </div>
        --}}
        </div>
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
