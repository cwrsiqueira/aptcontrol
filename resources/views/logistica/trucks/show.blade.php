@extends('layouts.template')

@section('title', 'Detalhes do Caminhão')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Caminhão</h2>
            <div>
                @if (in_array('trucks.update', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('trucks.edit', $truck) }}">Editar</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('trucks.index') }}">< Caminhões</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Responsável</dt>
                    <dd class="col-sm-9">{{ $truck->responsavel }}</dd>

                    <dt class="col-sm-3">Capacidade (paletes)</dt>
                    <dd class="col-sm-9">{{ $truck->capacidade_paletes }}</dd>

                    <dt class="col-sm-3">Modelo</dt>
                    <dd class="col-sm-9">{{ $truck->modelo ?? '—' }}</dd>

                    <dt class="col-sm-3">Placa</dt>
                    <dd class="col-sm-9">{{ $truck->placa ?? '—' }}</dd>

                    @if ($truck->obs)
                        <dt class="col-sm-3">Observações</dt>
                        <dd class="col-sm-9">{{ $truck->obs }}</dd>
                    @endif

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($truck->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($truck->updated_at)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </main>
@endsection
