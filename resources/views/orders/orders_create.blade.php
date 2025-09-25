@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4" style="height: 100vh;">
        <h2>Adicionar Pedido</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('orders.store') }}" method="post" id="form">
            @csrf
            <div class="row">
                <div class="col-sm-2">
                    <label for="order_number" class="form-label">Pedido Nr.: </label>
                    <input class="form-control @error('order_number') is-invalid @enderror order_number" type="search"
                        name="order_number" value="{{ old('order_number') ?? $seq_order_number }}" id="order_number"
                        placeholder="Digite o número do pedido"><small class="order_number_warning"
                        style="color:red;"></small>
                </div>
                <div class="col-sm-2">
                    <label for="order_date" class="form-label">Data: </label>
                    <input class="form-control" type="date" name="order_date" id="order_date"
                        value="{{ old('order_date') ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-sm-6">
                    <label for="client_name" class="form-label">Cliente: </label>
                    <input readonly class="form-control" type="text" name="client_name" id="client_name"
                        value="{{ $client->name }}"><input type="hidden" name="client_id" id="client_id"
                        value="{{ $client->id }}">
                </div>
                <div class="col-sm-2">
                    <label for="btn-adicionar-pedido" class="form-label" style="color: transparent;">Adicionar: </label>
                    <button id="btn-adicionar-pedido" type="submit"
                        class="btn btn-success form-control btn-adicionar-pedido">Adicionar</button>
                </div>
            </div>
        </form>
    </main>

@section('js')
    <script>
        $(function() {

            $('.order_number').blur(function() {
                if ($(this).val() !== '') {
                    $('.add_line').show();
                }
                let order_number = $(this).val();
                $.ajax({
                    url: '{{ route('search_order_number') }}',
                    type: 'get',
                    data: {
                        data: order_number
                    },
                    dataType: 'json',
                    success: function(json) {
                        $('.order_number').val(json);
                        if (json != order_number) {
                            $('.order_number_warning').html(
                                'Número já utilizado. Adicionado à sequência');
                            $('.order_number_align_size').html('.');
                        }
                    }
                })
            })
        });
    </script>
@endsection

@endsection
