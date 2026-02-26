@extends('layouts.template')

@section('title', 'Detalhes da Zona')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Zona</h2>
            <div>
                @if (in_array('zones.update', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('zones.edit', $zone) }}">Editar</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('zones.index') }}">< Zonas</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nome</dt>
                    <dd class="col-sm-9">{{ $zone->nome }}</dd>

                    <dt class="col-sm-3">Bairros</dt>
                    <dd class="col-sm-9">
                        @if ($zone->bairros->count() > 0)
                            <ul class="mb-0 pl-3">
                                @foreach ($zone->bairros as $b)
                                    <li>{{ $b->bairro_nome }}</li>
                                @endforeach
                            </ul>
                        @else
                            —
                        @endif
                    </dd>

                    @if ($zone->obs)
                        <dt class="col-sm-3">Observações</dt>
                        <dd class="col-sm-9">{{ $zone->obs }}</dd>
                    @endif

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($zone->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($zone->updated_at)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </main>
@endsection
