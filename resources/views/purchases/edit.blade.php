{{-- Tela de alteração do pedido enquanto está em rascunho. --}}
@extends('layouts.template')

@section('title', 'Editar Pedido de Compra')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">
        <h2>Editar Pedido de Compra {{ $purchase->number }}</h2>

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

        <form action="{{ route('purchases.update', $purchase) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            @include('purchases._form')
        </form>
    </main>
@endsection
