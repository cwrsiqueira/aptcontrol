@extends('layouts.estilos')

@section('title', 'Pedido')

@section('content')
    <main>
        @if ($order->complete_order === 1)
            <div class="delivered">
                ENTREGUE
            </div>
        @endif
        @if ($order->complete_order === 2)
            <div class="canceled">
                CANCELADO
            </div>
        @endif
    </main>
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Pedido nr. {{ $order->order_number }}</h2>

        <div class="row">
            <div class="col-md m-3">
                <div class="card-tools">
                    <a class="btn btn-sm btn-secondary" href="{{ route('orders.index') }}" id="btn_voltar">Voltar</a>
                    <button class="btn btn-sm btn-secondary"
                        onclick="
                        this.style.display = 'none';
                        document.getElementById('btn_voltar').style.display = 'none';
                        window.print();
                        javascript:history.go(0);
                    ">Imprimir</button>
                </div>
            </div>
            <div class="col-md m-1 d-flex justify-content-center">
                <label>Falta Entregar:</label>
                <ul>
                    @foreach ($saldo_produtos as $item)
                        <li>{{ $item->product_name }} = {{ number_format($item->saldo, 0, '', '.') }} <br></li>
                    @endforeach
                </ul>
            </div>
            @if ($order->complete_order !== 1 && $order->complete_order !== 2)
                <div class="col-md m-3 d-flex justify-content-end">
                    <div class="card-tools">
                        <button style="width:135px;" class="btn btn-sm btn-secondary btn-success" data-toggle="modal"
                            data-target="#confirmarEntrega">Confirmar Entrega</button>
                        <button style="width:135px" class="btn btn-sm btn-secondary btn-danger" data-toggle="modal"
                            data-target="#cancelarPedido">Cancelar Pedido</button>
                    </div>
                </div>
            @endif
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Data: <input readonly class="form-control" type="date" name="order_date" id="order_date"
                            value="{{ date('Y-m-d', strtotime($order->order_date)) }}"><input type="hidden" name="order_id"
                            id="order_id" value="{{ $order->id }}"></th>
                    <th colspan="2">Cliente: <input readonly class="form-control" type="text" name="client_name"
                            id="client_name" value="{{ $order->name_client }}"></th>
                </tr>
                <tr>
                    <th>Pedido Nr.: <input readonly class="form-control" type="text" name="order_number"
                            id="order_number" value="{{ $order->order_number }}"></th>
                    <th>Vendedor: <input readonly class="form-control" type="text" name="client_name" id="client_name"
                            value="{{ $order->seller_name ?? ' - ' }}"></th>
                    <th>
                        <label for="withdraw">Tipo de entrega:</label><br>
                        {{ Str::ucfirst($order->withdraw) }}
                        ({{ Str::lower($order->withdraw) == 'entregar' ? 'CIF' : 'FOB' }})
                    </th>
                </tr>
                <tr style="text-align: center;">
                    <th>Produto</th>
                    <th>Quant.</th>
                    <th>Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order_products as $item)
                    <tr style="text-align: center; @if ($item->quant < 0) color:red; @endif"">
                        <td style="padding: 5px;">
                            {{ $item->product_name }}
                        </td>
                        <td style="padding: 5px; id="quant_prod">
                            {{ number_format($item->quant, 0, '', '.') }}
                        </td>
                        <td style="padding: 5px;">
                            @if ($item->quant < 0)
                                {{ date('d/m/Y', strtotime($item->created_at)) }}
                            @else
                                {{ date('d/m/Y', strtotime($item->delivery_date)) }}
                            @endif
                        </td>
                    </tr>
                @endforeach

            </tbody>
        </table>

    </main>

    <!-- Modal Confirmar Entrega -->
    <div class="modal fade" id="confirmarEntrega" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Registrar Entrega do Produto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Produto:
                    <select name="produto" id="produto">
                        <option>Selecione o Produto:</option>
                        @foreach ($product as $key => $item)
                            <option value="{{ $key }}">{{ $item }}</option>
                        @endforeach
                    </select>
                    Quantidade:
                    <input type="search" name="quant" id="quant">
                    <input type="hidden" id="order_number" value="{{ $order->order_number }}">
                    <input type="hidden" id="order_id" value="{{ $order->id }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="deliver">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cancelar Pedido -->
    <div class="modal fade" id="cancelarPedido" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Cancelar Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                {{-- <div class="modal-body">
                    ...
                </div> --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Desistir</button>
                    <button type="button" class="btn btn-primary" id="cancel"
                        data-id="{{ $order->id }}">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        $(function() {

            $('#quant_prod').mask('000.000.000', {
                reverse: true
            });
            $('#quant').mask('000.000.000', {
                reverse: true
            });

            $('#produto').change(function() {
                let id_prod = $(this).val();
                let order = $('#order_number').val();
                inserir_quant_produto(id_prod, order);
            });

            function inserir_quant_produto(id, order) {
                $.ajax({
                    url: "{{ route('saldo_produto') }}",
                    type: 'get',
                    data: {
                        id: id,
                        order: order
                    },
                    dataType: 'json',
                    success: function(json) {
                        function formatar(nr) {
                            return String(nr)
                                .split('').reverse().join('').split(/(\d{3})/).filter(Boolean)
                                .join('.').split('').reverse().join('');
                        }
                        let formatado = formatar(json['saldo']);
                        $('#quant').val(formatado);
                        $('#quant').attr('max', formatado);
                    }
                });
            }

            $('#deliver').click(function() {

                if (confirm('Deseja confirmar a entrega?')) {
                    let id = $('#order_id').val();
                    let order = $('#order_number').val();
                    let id_prod = $('#produto').val();
                    let quant = $('#quant').val();
                    let max = $('#quant').attr('max');
                    let delivered = '';

                    quant = parseInt(quant.replace(/[^\d]+/g, ''));
                    max = parseInt(max.replace(/[^\d]+/g, ''));

                    if (quant > max) {
                        alert('Você não pode entregar mais que o saldo do pedido');
                        inserir_quant_produto(id_prod, order);
                        return false;
                    } else if (quant == max) {
                        delivered = 'total';
                    } else if (quant < max) {
                        delivered = 'parcial';
                    }

                    $.ajax({
                        url: "{{ route('register_delivery') }}",
                        type: 'get',
                        data: {
                            id: id,
                            id_prod: id_prod,
                            quant: quant,
                            delivered: delivered
                        },
                        dataType: 'json',
                        success: function(json) {
                            if (delivered === 'parcial') {
                                window.location.href = 'orders_conclude?order=' + json;
                            } else {
                                window.location.href = 'orders/' + json;
                            }
                        }
                    });

                } else {
                    return false;
                }
            })

            $('#cancel').click(function() {
                if (confirm('Deseja cancelar o pedido?')) {
                    let id = $(this).attr('data-id');
                    $.ajax({
                        url: "{{ route('register_cancel') }}",
                        type: 'get',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(json) {
                            window.location.href = 'orders/' + json;
                        }
                    });
                } else {
                    return false;
                }
            })
        })
    </script>
@endsection

@section('css')
    <style>
        .delivered {
            position: absolute;
            top: 50%;
            width: 100%;
            text-align: center;
            font-size: 18px;
            font-size: 150px;
            font-weight: bold;
            color: rgba(110, 168, 108, 0.6);
            transform: rotate(-45deg);
            z-index: 9;
        }

        .canceled {
            position: absolute;
            top: 50%;
            width: 100%;
            text-align: center;
            font-size: 18px;
            font-size: 150px;
            font-weight: bold;
            color: rgba(165, 74, 74, 0.6);
            transform: rotate(-45deg);
            z-index: 9;
        }
    </style>

@endsection
