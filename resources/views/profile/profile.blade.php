@extends('layouts.template')

@section('title', 'Meu Perfil')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Meu Perfil</h2>
        </div>

        {{-- Alerts de erro/sucesso --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        <div class="card card-lift">
            <div class="card-header">
                <strong>Dados do Perfil</strong>
            </div>
            <form action="{{ route('users.update', $user) }}" method="post" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ old('name', $user->name) }}" required maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" class="form-control" value="{{ $user->email }}" readonly>
                    </div>

                    <hr>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="password">Nova senha</label>
                            <input type="password" name="password" id="password" class="form-control"
                                autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="password_confirmation">Confirmar nova senha</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="form-control" autocomplete="new-password" placeholder="Repita a nova senha">
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </main>
@endsection

@section('css')
    <style>
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .page-header h2 {
            font-weight: 600;
        }
    </style>
@endsection
