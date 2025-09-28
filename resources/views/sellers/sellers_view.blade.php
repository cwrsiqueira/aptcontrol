@extends('layouts.template')

@section('title', 'Detalhes do Vendedor')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Vendedor</h2>
            <div>
                @if (in_array('sellers.update', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('sellers.edit', $seller->id) }}">Editar</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('sellers.index') }}">
                    < Vendedores</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nome</dt>
                    <dd class="col-sm-9">{{ $seller->name }}</dd>

                    <dt class="col-sm-3">Tipo de Contato</dt>
                    <dd class="col-sm-9">
                        @switch($seller->contact_type)
                            @case('whatsapp')
                                WhatsApp
                            @break

                            @case('telefone')
                                Telefone
                            @break

                            @case('email')
                                Email
                            @break

                            @case('instagram')
                                Instagram
                            @break

                            @default
                                Outro
                        @endswitch
                    </dd>

                    <dt class="col-sm-3">Contato</dt>
                    <dd class="col-sm-9">{{ $seller->contact_value }}</dd>

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($seller->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($seller->updated_at)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </main>
@endsection
