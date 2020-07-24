@extends('layouts.template')

@section('title', 'Relatórios')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Relatórios</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Relação de Entregas</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{route('report_delivery')}}" method="get">
                            <label for="delivery_date">Selecione a data:</label>
                            <input class="form-control" type="date" name="delivery_date" id="delivery_date" value="{{date('Y-m-d')}}">
                            <input class="btn btn-sm btn-secondary m-3" type="submit" value="Buscar">
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Relação de Entregas por período</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{route('report_delivery_byPeriod')}}" method="get">
                            <label for="delivery_date">Selecione a data inicial:</label>
                            <input class="form-control" type="date" name="date_ini" id="delivery_date" value="{{date('Y-m-d')}}">
                            <label for="delivery_date">Selecione a data final:</label>
                            <input class="form-control" type="date" name="date_fin" id="delivery_date" value="{{date('Y-m-d')}}">
                            <input class="btn btn-sm btn-secondary m-3" type="submit" value="Buscar">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection