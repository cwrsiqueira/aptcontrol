@extends('layouts.template')

@section('title', 'Logística')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-4">
        <h2>Logística</h2>
        <p class="text-muted">Gerencie caminhões e zonas de entrega.</p>

        <div class="card bg-light">
            <div class="card-body">
                <div class="list-group">

                    <a href="{{ route('trucks.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-truck mr-2"></i>
                        Caminhões
                    </a>

                    <a href="{{ route('zones.index') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Zonas
                    </a>

                </div>
            </div>
        </div>
    </main>
@endsection
