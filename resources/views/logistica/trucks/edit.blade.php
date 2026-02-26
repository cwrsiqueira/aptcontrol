@extends('layouts.template')

@section('title', 'Editar Caminhão')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Caminhão</h2>

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
                <form action="{{ route('trucks.update', $truck) }}" method="post" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="responsavel">Responsável *</label>
                        <input type="text" class="form-control @error('responsavel') is-invalid @enderror"
                            id="responsavel" name="responsavel" maxlength="150" required value="{{ old('responsavel', $truck->responsavel) }}"
                            placeholder="Responsável pela manutenção do caminhão">
                    </div>

                    <div class="form-group">
                        <label for="capacidade_paletes">Capacidade (paletes) *</label>
                        <input type="number" class="form-control @error('capacidade_paletes') is-invalid @enderror"
                            id="capacidade_paletes" name="capacidade_paletes" min="0" required value="{{ old('capacidade_paletes', $truck->capacidade_paletes) }}">
                    </div>

                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" maxlength="100" value="{{ old('modelo', $truck->modelo) }}">
                    </div>

                    <div class="form-group">
                        <label for="placa">Placa</label>
                        <input type="text" class="form-control" id="placa" name="placa" maxlength="20" value="{{ old('placa', $truck->placa) }}">
                    </div>

                    <div class="form-group">
                        <label for="obs">Observações</label>
                        <textarea class="form-control" id="obs" name="obs" rows="3">{{ old('obs', $truck->obs) }}</textarea>
                    </div>

                    <button class="btn btn-primary">Salvar alterações</button>
                    <a class="btn btn-light" href="{{ route('trucks.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
