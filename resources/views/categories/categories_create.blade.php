@extends('layouts.template')

@section('title', 'Categorias de clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Cadastrar Categoria</h2>

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
                <form action="{{ route('categories.store') }}" method="post" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="name">Nome:</label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                            placeholder="Nome da categoria" id="name" value="{{ old('name') }}">
                    </div>

                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light" href="{{ route('categories.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
