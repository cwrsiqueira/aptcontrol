@extends('layouts.template')

@section('title', 'Entregar produto')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="d-flex justify-content-between align-items-center page-header mb-2">
            <h2 class="page-title mb-0">Entregar produto</h2>
            <a class="btn btn-sm btn-light"
                href="{{ route('order_products.index', ['order' => $order_product->order->id]) }}">
                < Detalhes do pedido</a>
        </div>

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

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif


        <div class="card card-lift items-card">
            <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex flex-wrap align-items-center">
                    <strong class="mr-2">{{ $order_product->product->name }}</strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('order_products.delivered', ['order_product' => $order_product]) }}" method="post"
                    novalidate>
                    @csrf

                    <input type="hidden" class="form-control @error('product_name') is-invalid @enderror" id="product_name"
                        name="product_name" value="{{ $order_product->product->name }}">
                    <div class="row">
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="quant">Quantidade a entregar <small>(Saldo)</small></label>
                                <input type="text" name="quant" id="quant"
                                    class="form-control @error('quant') is-invalid @enderror qt"
                                    placeholder="Quantidade máxima: {{ $saldo_produto->saldo * -1 }}"
                                    value="{{ $saldo_produto->saldo }}">
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="form-group">
                                <label for="delivery_date">Data da entrega</label>
                                <input type="date" name="delivery_date" id="delivery_date"
                                    class="form-control @error('delivery_date') is-invalid @enderror"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-sm">
                            <span class="muted-label">Saldo</span>
                            <ul>
                                <li class="text-muted">Pedido - entregue = saldo:
                                    <ul class="font-weight-bold">
                                        {{ number_format($saldo_produto->saldo_inicial, 0, '', '.') }}
                                        -
                                        {{ number_format($saldo_produto->saldo_inicial - $saldo_produto->saldo, 0, '', '.') }}
                                        =
                                        {{ number_format($saldo_produto->saldo, 0, '', '.') }} <br></ul>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <button class="btn btn-primary" id="confirm-delivery">Confirmar entrega</button>
                    <a class="btn btn-light"
                        href="{{ route('order_products.index', ['order' => $order_product->order]) }}">Cancelar</a>
                </form>
            </div>
        </div>

        <div class="card card-lift mb-5">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            <th>#</th>
                            <th>Data da entrega</th>
                            <th class="text-right">Quantidade</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($delivered as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}</td>
                                <td class="text-right">{{ number_format($item->quant, 0, '', '.') }}</td>
                                <td class="text-right">

                                    @if (in_array('orders.update', $user_permissions) || Auth::user()->is_admin)
                                        <form
                                            action="{{ route('order_products.destroy', ['order_product' => $item, 'main_order_product' => $order_product->id]) }}"
                                            method="post" style="display:inline-block"
                                            onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-danger" disabled
                                            title="Solicitar Acesso">Excluir</button>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum item encontrado para este
                                    pedido.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>
@endsection

@section('js')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mask.min.js') }}"></script>p
    <script>
        $('.qt').mask('000.000.000', {
            reverse: true
        });

        document.querySelector('#confirm-delivery').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Confirmar a entrega?')) {
                this.setAttribute('disabled', true);
                this.form.submit();
            }
        });
    </script>
@endsection
