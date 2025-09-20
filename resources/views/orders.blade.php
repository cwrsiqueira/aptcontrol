@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <!-- The Modal -->
        <div class="modal" id="delete_order_repeated">
            <div class="modal-dialog">
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">{{ count($orders_repeated) }} Pedido(s) em Duplicidade
                            @if (Auth::user()->is_admin)
                                <br><small>Exclua um dos pedidos em duplicidade
                                @else
                                    <br><small>Solicite a regularização a um administrador
                            @endif
                            <br><span style="color:red;">Atenção: Os relatórios de produtos e de entrega ficarão
                                comprometidos até a regularização</span></small>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pedido nr.</th>
                                    <th>Valor do Pedido</th>
                                    <th colspan="2">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders_repeated as $item)
                                    @foreach ($item as $order)
                                        <tr>
                                            <td><span>{{ $order->order_number }}</span></td>
                                            <td><span>{{ $order->order_total }}</span></td>
                                            <td><a href="{{ route('orders.show', ['order' => $order->id]) }}">Detalhar</a>
                                            </td>
                                            <td>
                                                @if (Auth::user()->is_admin)
                                                    <a href="#" class="del_dup_order"
                                                        data-id="{{ $order->id }}">Deletar</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>

        <h2>Pedidos</h2>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if ($errors->has('cannot_exclude') || $errors->has('no-access'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <h5>
                    <i class="icon fas fa-ban"></i>
                    Erro!!!
                </h5>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="get" class="d-flex justify-content-between">
            <div class="form-check m-3">
                <label class="form-check-label">
                    @if (in_array('16', $user_permissions) || Auth::user()->is_admin)
                        <input onclick="this.form.submit();" type="checkbox" class="form-check-input" name="comp"
                            @if (@$_GET['comp'] === '1') checked @endif value="1">Mostrar Pedidos
                        Concluídos/Cancelados
                    @else
                        <input disabled title="Solicitar Acesso" type="checkbox" class="form-check-input">Mostrar Pedidos
                        Concluídos/Cancelados
                    @endif
                </label>
            </div>

            <div class="d-flex align-items-center">
                @if (!empty($q))
                    <a class="btn btn-sm btn-secondary mr-3" style="width:160px" href="{{ route('orders.index') }}">Limpar
                        Busca</a>
                @endif
                <input type="search" class="form-control" name="q" id="q" placeholder="Procurar Pedido"
                    value="{{ $q }}">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-default">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nr.Pedido</th>
                        <th>Dt Pedido</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
                        <th>Entrega</th>
                        <th colspan="3" style="text-align: center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $item): ?>
                    <tr>
                        <td><?php echo $item['order_number']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($item['order_date'])); ?></td>
                        <td><?php echo $item['name_client']; ?></td>
                        <td>{{ $item['payment'] }}</td>
                        <td>{{ $item['withdraw'] }}</td>
                        <td>
                            @if (in_array('17', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-secondary"
                                    href="{{ route('orders.show', ['order' => $item->id]) }}">Visualizar</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled
                                    title="Solicitar Acesso">Visualizar</button>
                            @endif
                        </td>
                        @if ($item->complete_order == 1 || $item->complete_order == 2)
                            @if ($item->complete_order == 1)
                                <td colspan="2" style="text-align: center; font-weight:bold;color:green">
                                    Entregue
                                </td>
                            @endif
                            @if ($item->complete_order == 2)
                                <td colspan="2" style="text-align: center; font-weight:bold;color:red">
                                    Cancelado
                                </td>
                            @endif
                        @else
                            <td>
                                @if (in_array('18', $user_permissions) || Auth::user()->is_admin)
                                    <a class="btn btn-sm btn-secondary"
                                        href="{{ route('orders.edit', ['order' => $item->id]) }}">Editar</a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled
                                        title="Solicitar Acesso">Editar</button>
                                @endif
                            </td>
                            <td>
                                @if (in_array('19', $user_permissions) || Auth::user()->is_admin)
                                    <a class="btn btn-sm btn-secondary"
                                        href="{{ route('orders_conclude', ['order' => $item->id]) }}">Concluir</a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled
                                        title="Solicitar Acesso">Concluir</button>
                                @endif
                            </td>
                        @endif
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            @if ($comp === 1)
                {{ $orders->appends(['q' => $q ?? ''])->appends(['comp' => $comp])->links() }}
            @else
                {{ $orders->appends(['q' => $q ?? ''])->links() }}
            @endif
        </div>
    </main>
@endsection

@section('js')
    <script>
        $(function() {

            // Receber e deletar ordens duplicadas
            let orderRepeated = '{{ count($orders_repeated) }}';
            if (orderRepeated > 0) {
                $('#delete_order_repeated').modal();
            }
            $('.del_dup_order').click(function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                if (confirm('Confirma a Exclusão do Pedido?')) {
                    $.ajax({
                        url: "{{ route('del_dup_order') }}",
                        type: 'get',
                        data: {
                            id: id
                        },
                        success: function() {
                            window.location.reload();
                        },
                    });
                }
            })

            $('.complete_order').click(function() {
                if (confirm('Confirma a entrega do pedido?')) {
                    let id = $(this).attr('data-id');

                    $.ajax({
                        url: "{{ route('edit_complete_order') }}",
                        type: 'get',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(json) {
                            alert(json);
                            window.location.href = 'orders';
                        },
                    });
                } else {
                    return false;
                }

            })
        })
    </script>
@endsection
