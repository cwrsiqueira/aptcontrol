@extends('layouts.template')

@section('title', 'Vendedores')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Cadastrar Vendedor</h2>

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
                <form action="{{ route('sellers.store') }}" method="post" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text"
                            class="form-control @error('name')
                            is-invalid
                        @enderror"
                            id="name" name="name" maxlength="150" required value="{{ old('name') }}">
                    </div>

                    <div class="form-group">
                        <label for="contact_type">Tipo de Contato</label>
                        <select
                            class="form-control @error('name')
                            is-invalid
                        @enderror"
                            id="contact_type" name="contact_type" required>
                            <option value="">Selecione...</option>
                            <option value="whatsapp" {{ old('contact_type') == 'whatsapp' ? 'selected' : '' }}>WhatsApp
                            </option>
                            <option value="telefone" {{ old('contact_type') == 'telefone' ? 'selected' : '' }}>Telefone
                            </option>
                            <option value="email" {{ old('contact_type') == 'email' ? 'selected' : '' }}>Email</option>
                            <option value="instagram" {{ old('contact_type') == 'instagram' ? 'selected' : '' }}>Instagram
                            </option>
                            <option value="outro" {{ old('contact_type') == 'outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_value">Contato</label>
                        <input type="text" class="form-control" id="contact_value" name="contact_value" maxlength="191"
                            value="{{ old('contact_value') }}" placeholder="ex.: (96) 99999-9999, email, @usuario...">
                    </div>

                    <button class="btn btn-primary">Salvar</button>
                    <a class="btn btn-light" href="{{ route('sellers.index') }}">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
@endsection
