@extends('layouts.template')

@section('title', 'Nova Permissão')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Nova Permissão</h2>
            <a href="{{ route('permission-items.index') }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card card-lift">
            <div class="card-body">
                <form method="POST" action="{{ route('permission-items.store') }}">
                    @csrf

                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" name="slug" class="form-control" maxlength="100"
                            value="{{ old('slug') }}" placeholder="ex.: menu-produtos ou products.create" required>
                    </div>

                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="name" class="form-control" maxlength="150"
                            value="{{ old('name') }}" placeholder="ex.: Menu Produtos" required>
                    </div>

                    <div class="form-group">
                        <label>Grupo</label>
                        <input type="text" name="group_name" class="form-control" maxlength="100"
                            value="{{ old('group_name') }}" placeholder="ex.: Menus, Produtos, Pedidos...">
                    </div>

                    <button class="btn btn-success" type="submit">Salvar</button>
                </form>
            </div>
        </div>
    </main>
@endsection
