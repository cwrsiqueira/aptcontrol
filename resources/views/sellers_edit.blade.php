@extends('layouts.template')

@section('title', 'Editar Vendedor')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Editar Vendedor</h2>

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
                <form action="{{ route('sellers.update', $seller->id) }}" method="post" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="150" required
                            value="{{ old('name', $seller->name) }}">
                    </div>

                    <div class="form-group">
                        <label for="contact_type">Tipo de Contato</label>
                        <select class="form-control" id="contact_type" name="contact_type" required>
                            @php $ct = old('contact_type', $seller->contact_type); @endphp
                            <option value="">Selecione...</option>
                            <option value="whatsapp" {{ $ct == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="telefone" {{ $ct == 'telefone' ? 'selected' : '' }}>Telefone</option>
                            <option value="email" {{ $ct == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="instagram" {{ $ct == 'instagram' ? 'selected' : '' }}>Instagram</option>
                            <option value="outro" {{ $ct == 'outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_value">Contato</label>
                        <input type="text" class="form-control" id="contact_value" name="contact_value" maxlength="191"
                            value="{{ old('contact_value', $seller->contact_value) }}">
                    </div>

                    <button class="btn btn-primary">Salvar alterações</button>
                    <a class="btn btn-light" href="{{ route('sellers.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
