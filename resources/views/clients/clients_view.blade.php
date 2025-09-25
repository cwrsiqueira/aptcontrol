@extends('layouts.template')

@section('title', 'Clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Cliente</h2>
            <div>
                @if (in_array('clients.view', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('clients.edit', $client) }}">Editar</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('clients.index') }}">Voltar</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nome</dt>
                    <dd class="col-sm-9">{{ $client->name }}</dd>

                    <dt class="col-sm-3">Categoria</dt>
                    <dd class="col-sm-9">{{ $client->category->name }}</dd>

                    <dt class="col-sm-3">Contato</dt>
                    <dd class="col-sm-9">{{ $client->contact }}</dd>

                    <dt class="col-sm-3">Endereço</dt>
                    <dd class="col-sm-9">{{ $client->full_address }}</dd>

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($client->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($client->updated_at)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </main>
@endsection
