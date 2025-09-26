@extends('layouts.template')

@section('title', 'Clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Cliente</h2>

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
                <form action="{{ route('clients.update', $client) }}" method="post" novalidate>
                    @method('PUT')
                    @csrf

                    <div class="form-group">
                        <label for="name">Nome:</label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                            placeholder="Nome do cliente" id="name" value="{{ old('name') ?? $client->name }}">
                    </div>

                    <div class="form-group">
                        <label for="id_category">Categoria:</label>
                        <select name="id_category" id="id_category" class="form-control">
                            @foreach ($categories as $item)
                                <option @if ((old('id_category') ?? $client->id_categoria) == $item->id) selected @endif value="{{ $item->id }}">
                                    {{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contato:</label>
                        <input class="form-control @error('contact') is-invalid @enderror" type="text" name="contact"
                            placeholder="Whatsapp, telefone etc..." id="contact"
                            value="{{ old('contact') ?? $client->contact }}">
                    </div>

                    <div class="form-group">
                        <label for="full_address">Endereço completo:</label>
                        <textarea class="form-control" name="full_address" id="full_address" placeholder="Endereço completo">{{ old('full_address') ?? $client->full_address }}</textarea>
                    </div>

                    <button class="btn btn-primary">Salvar alterações</button>
                    <a class="btn btn-light" href="{{ route('clients.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
