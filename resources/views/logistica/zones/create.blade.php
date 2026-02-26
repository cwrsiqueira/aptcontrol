@extends('layouts.template')

@section('title', 'Cadastrar Zona')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Cadastrar Zona</h2>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('zones.store') }}" method="post" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="nome">Nome da zona *</label>
                        <input type="text" class="form-control @error('nome') is-invalid @enderror"
                            id="nome" name="nome" maxlength="100" required value="{{ old('nome') }}"
                            placeholder="ex.: Zona Norte, Centro">
                    </div>

                    <div class="form-group">
                        <label for="bairros">Bairros pertencentes</label>
                        <textarea class="form-control" id="bairros" name="bairros" rows="5"
                            placeholder="Um bairro por linha ou separados por vírgula">{{ old('bairros', $bairrosTexto ?? '') }}</textarea>
                        <small class="form-text text-muted">Digite um bairro por linha ou separados por vírgula/ponto-e-vírgula.</small>
                    </div>

                    <div class="form-group">
                        <label for="obs">Observações</label>
                        <textarea class="form-control" id="obs" name="obs" rows="3">{{ old('obs') }}</textarea>
                    </div>

                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light" href="{{ route('zones.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
