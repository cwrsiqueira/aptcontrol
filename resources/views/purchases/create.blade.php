{{-- Tela de cadastro do pedido e dos itens solicitados. --}}
@extends('layouts.template')

@section('title', 'Cadastrar Pedido de Compra')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">
        <h2>Cadastrar Pedido de Compra</h2>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchases.store') }}" method="POST" novalidate>
            @csrf
            @include('purchases._form')
        </form>
    </main>
@endsection
