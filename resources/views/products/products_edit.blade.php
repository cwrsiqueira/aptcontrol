@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Produto</h2>

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
                <form action="{{ route('products.update', ['product' => $product]) }}" method="post" novalidate>
                    @method('PUT')
                    @csrf

                    <div class="form-group">
                        <label for="name">Nome do produto:</label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                            placeholder="Nome do Produto" id="name" value="{{ old('name') ?? $product->name }}">
                    </div>

                    <div class="form-group">
                        <label for="stock">Estoque inicial:</label>
                        <input class="form-control @error('stock') is-invalid @enderror quant-format" type="text"
                            name="stock" placeholder="Sem estoque inicial" id="stock"
                            value="{{ old('stock') ?? $product->current_stock }}">
                    </div>

                    <div class="form-group">
                        <label for="forecast">Previsão diária de produção:</label>
                        <input class="form-control @error('forecast') is-invalid @enderror quant-format" type="text"
                            name="forecast" placeholder="Previsão média diária" id="forecast"
                            value="{{ old('forecast') ?? $product->daily_production_forecast }}" min="1">
                    </div>

                    <button class="btn btn-primary">Salvar alterações</button>
                    <a class="btn btn-light" href="{{ route('products.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
@section('js')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>
    <script>
        $('.quant-format').mask('000.000.000', {
            reverse: true
        });
    </script>
@endsection
