@extends('layouts.template')

@section('title', 'Detalhes do Produto')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center">
            <h2>Produto</h2>
            <div>
                @if (in_array('products.view', $user_permissions) || Auth::user()->is_admin)
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $product) }}">Editar</a>
                @endif
                <a class="btn btn-sm btn-light" href="{{ route('products.index') }}">Voltar</a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Nome</dt>
                    <dd class="col-sm-9">{{ $product->name }}</dd>

                    <dt class="col-sm-3">Estoque inicial</dt>
                    <dd class="col-sm-9">{{ $product->current_stock }}</dd>

                    <dt class="col-sm-3">Previsão diária de produção</dt>
                    <dd class="col-sm-9">{{ $product->daily_production_forecast }}</dd>

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($product->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($product->updated_at)->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </main>
@endsection
