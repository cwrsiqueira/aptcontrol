@extends('layouts.template')
@section('title', 'Pedido')
@section('content')

    {{-- Modal Loader --}}
    <div class="modal" id="loader">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: transparent;border:0;">

                <!-- Modal body -->
                <div class="modal-body" style="text-align: center;">
                    <div class="spinner-border" style="color: #fff;width:100px;height:100px;"></div>
                    <p style="color: #fff;font-size:24px;font-weight:bold;">Aguarde...</p>
                </div>

            </div>
        </div>
    </div>

    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Editar Pedido nr. {{ $order->order_number }}</h2>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('orders.update', ['order' => $order->id]) }}" method="post" id="form">
            @csrf
            @method('PUT')
            <table class="table">
                <thead>
                    <tr>
                        <th colspan="1">Data: <input class="form-control" type="date" name="order_date"
                                id="order_date" value="{{ date('Y-m-d', strtotime($order->order_date)) }}"><input
                                type="hidden" name="order_id" id="order_id" value="{{ $order->id }}"></th>
                        <th colspan="5">Cliente: <input readonly class="form-control" type="text" name="client_name"
                                id="client_name" value="{{ $order->name_client }}"></th>
                    </tr>
                    <tr>
                        <th colspan="1">Pedido Nr.:
                            <input class="form-control" type="text" name="order_number" id="order_number"
                                value="{{ $order->order_number }}" readonly>

                            <input class="form-control" type="hidden" name="order_old_number" id="order_old_number"
                                value="{{ $order->order_number }}">
                        </th>
                        <th colspan="2">Valor do Pedido: <input class="form-control" readonly type="text"
                                name="total_order" id="total_order"
                                value="{{ number_format($order->order_total, 2, ',', '.') }}"></th>
                        <th colspan="1">
                            Pagamento:
                            <select class="form-control" name="payment" id="payment">
                                <option @if ($order->payment == 'Aberto') selected @endif value="Aberto">Aberto</option>
                                <option @if ($order->payment == 'Parcial') selected @endif value="Parcial">Parcial</option>
                                <option @if ($order->payment == 'Total') selected @endif value="Total">Total</option>
                            </select>
                        </th>

                        <th colspan="1">
                            Recebimento do Material:
                            <select class="form-control" name="withdraw" id="withdraw">
                                <option @if ($order->withdraw == 'Entregar') selected @endif value="Entregar">Entregar</option>
                                <option @if ($order->withdraw == 'Retirar') selected @endif value="Retirar">Retirar</option>
                            </select><small class="order_number_align_size" style="color:transparent;"></small>
                        </th>
        </form>
        @if ($order->complete_order == 0)
            <th colspan="1" style="text-align: center"><a class="add_line" style='color:green;' href='#'
                    data-toggle='tooltip' title='Adicionar linha!'><i class='fas fa-fw fa-plus'
                        style="font-size: 24px;"></i></a><br><small class="order_number_align_size"
                    style="color:transparent;"></small></th>
        @endif
        </tr>
        <tr style="text-align: center;">
            <th>Produto</th>
            <th>Quant.</th>
            <th>Vlr.Unit.</th>
            <th>Vlr.Total</th>
            <th>Entrega</th>
            <th colspan="2">Linha</th>
        </tr>
        </thead>
        <tbody class="table-prod">

            @foreach ($order_products as $item)
                <tr class="@if ($item->quant < 0) color-red @endif" style="text-align: center;">
                    <td style="padding: 5px;">
                        @foreach ($products as $product)
                            <option class=" @if ($item->quant < 0) color-red @endif"
                                @if ((int) $item->product_id !== (int) $product->id) style="display:none;" @else selected @endif
                                value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </td>
                    <td style="padding: 5px;">{{ number_format($item->quant, 0, '', '.') }}</td>
                    <td style="padding: 5px;">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 5px;">{{ number_format($item->total_price, 2, ',', '.') }}</td>
                    <td style="padding: 5px;">
                        @if ($item->quant < 0)
                            {{ date('d/m/Y', strtotime($item->created_at)) }}
                        @else
                            {{ date('d/m/Y', strtotime($item->delivery_date)) }}
                        @endif
                    </td>
                    <td>
                        @if ($order->complete_order == 0)
                            <form title="Excluir Linha!"
                                action="{{ route('orders.order_product_destroy', ['order_product' => $item]) }}"
                                method="POST" onsubmit="return confirm('Confirma a exclusão da Linha?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class='far fa-trash-alt'
                                        style="font-size: 16px;"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach

        </tbody>
        </table>
        <hr>
        @if ($order->complete_order == 0)
            <div class="d-flex align-items-center mb-5">
                <input class="btn btn-info mr-3" type="submit" value="Concluir">
            </div>
        @endif
    </main>

    <!-- MODAL ADICIONAR PRODUTO -->
    <div class="modal fade" id="modal_addLine">
        <div class="modal-dialog modal-lg">
            <form method="post" action="{{ route('add_line') }}" id="form_add_cliente">
                @csrf
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Adicionar Produto</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    {{-- Show errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Modal body -->
                    <div class="modal-body">

                        <input type="hidden" name="order_id" value="{{ $order->order_number }}">
                        <input type="hidden" name="id_order" value="{{ $order->id }}">

                        <div class="row">
                            <div class="col-sm-3"><label for="product_id">Produto:</label>
                                <select required class="form-control @error('product_id') is-invalid @enderror"
                                    name="product_id" id="prod">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm">
                                <label for="quant">Quantidade:</label>
                                <input required class="form-control quant @error('quant') is-invalid @enderror"
                                    style="width: 100%;" type="text" name="quant" value="" placeholder="0">
                            </div>

                            <div class="col-sm">
                                <label for="unit_price">Preço/Milheiro:</label>
                                <input required class="form-control valor @error('unit_price') is-invalid @enderror"
                                    style="width: 100%;" type="text" name="unit_price" placeholder="0,00"
                                    value="">
                            </div>

                            <div class="col-sm">
                                <label>Valor Total:</label>
                                <input required class="form-control total_val" type="text" readonly value="0,00">
                            </div>

                            <div class="col-sm-3">
                                <label for="delivery_date">Previsão de Entrega:</label>
                                <input required class="form-control delivery_date @error('valor') is-invalid @enderror"
                                    style="width: 100%;" type="date" name="delivery_date" id="delivery_date"
                                    readonly>
                            </div>

                        </div>

                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer justify-content-between">
                        <input type="submit" class="btn btn-warning btn-salvar" value="Preencha todos os campos"
                            disabled>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                    </div>

                </div>

            </form>
        </div>
    </div>

@endsection

@section('css')
    <style>
        .color-red {
            color: red;
        }
    </style>
@endsection

@section('js')

    @if (empty($_GET['q']) && !empty($_GET['new']))
        <script>
            $(function() {
                $('#modal_addLine').modal();
            })
        </script>
    @endif

    <script>
        $(function() {

            $('#loader').modal('show');
            $("#modal_addLine").on('shown.bs.modal', function() {
                $('#loader').modal('hide');
            });

            $('.valor').mask('000.000,00', {
                reverse: true
            });
            $('.quant').mask('000.000', {
                reverse: true
            });

            $('.add_line').click(function() {
                $('#modal_addLine').modal();
            })

            picker = document.getElementById('delivery_date');
            picker.addEventListener('input', function(e) {
                var day = new Date(this.value).getUTCDay();
                if ([0].includes(day)) {
                    e.preventDefault();
                    calc_delivery_date($('.quant').val());
                    alert('Agendamento para domingo não permitido!');
                }
            });

            function calc_delivery_date(obj) {
                let id = $('#prod').val();
                let quant = obj;
                if (id === '') {
                    alert('Selecionar o produto');
                    $('#prod').focus();
                } else {
                    $.ajax({
                        url: "{{ route('day_delivery_calc') }}",
                        type: 'get',
                        data: {
                            id: id,
                            quant: quant
                        },
                        dataType: 'json',
                        success: function(json) {
                            $('.delivery_date').val(json);
                        },
                    });
                }
            }

            // Calcular Dia de Entrega
            $('.quant').blur(function() {
                let quant = $(this).val().replace(/[^\d]+/g, '');
                calc_delivery_date(quant);
            })

            // Calcular Valor Total do Produto
            $('.valor').blur(function() {
                let valor = $(this).val().replace('.', '').replace(',', '.');
                let total_val = ($('.quant').val().replace('.', '') * valor) / 1000;
                let formatado = total_val.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2
                });
                $('.total_val').val(formatado);
                $('.btn-salvar').attr('disabled', false).attr('class', 'btn btn-success btn-salvar').val(
                    'Salvar');
            })

        });
    </script>

    @if (empty($_GET['q']) && empty($_GET['new']))
        <script>
            $(function() {
                $('#loader').modal('hide');
            })
        </script>
    @endif
@endsection
