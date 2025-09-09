@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Produtos</h2>

        {{-- Mostra errors --}}
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

        {{-- Cadastra produtos, Busca e Tabela Lista de Produtos Cadastrados --}}
        <div>
            <div class="d-flex justify-content-between">

                <button class="btn btn-secondary my-3" data-toggle="modal" data-target="#modal_addproduto"
                    @if (in_array('7', $user_permissions) || Auth::user()->is_admin) @else disabled title="Solicitar Acesso" @endif>Cadastrar
                    Produto</button>

                <form method="get" class="d-flex align-items-center">
                    @if (!empty($q ?? ''))
                        <a class="btn btn-sm btn-secondary m-3" href="{{ route('products.index') }}">Limpar Busca</a>
                    @endif
                    <input type="search" class=" form-control" name="q" id="q" placeholder="Procurar Produto"
                        value="{{ $q ?? '' }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

            </div>

            <table class="table" style="text-align: center">
                <thead>
                    <tr>
                        <th>#Ref.</th>
                        <th>Nome</th>
                        {{-- <th>Estoque</th> --}}
                        <th>Previsão Diária</th>
                        <th colspan="4" style="text-align:center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo $item['name']; ?></td>
                        {{-- <td><?php echo number_format($item['current_stock'], 0, '', '.'); ?></td> --}}
                        <td><?php echo number_format($item['daily_production_forecast'], 0, '', '.'); ?></td>
                        <td>
                            @if (in_array('8', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-secondary"
                                    href="{{ route('products.edit', ['product' => $item->id, 'action' => 'edit']) }}">Editar</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Editar</button>
                            @endif
                        </td>
                        {{-- <td>
                            @if (in_array('9', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-secondary"
                                    href="{{ route('products.edit', ['product' => $item->id, 'action' => 'add_estock']) }}">+
                                    Estoque</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">+
                                    Estoque</button>
                            @endif
                        </td> --}}
                        <td>
                            @if (in_array('10', $user_permissions) || Auth::user()->is_admin)
                                <a class="btn btn-sm btn-secondary"
                                    href="{{ route('cc_product', ['id' => $item->id]) }}">C/C</a>
                            @else
                                <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">C/C</button>
                            @endif
                        </td>
                        <td>
                            @if (in_array('11', $user_permissions) || Auth::user()->is_admin)
                                <form title="Excluir" action=" {{ route('products.destroy', ['product' => $item->id]) }} "
                                    method="POST" onsubmit="return confirm('Confirma a exclusão do produto?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class='far fa-trash-alt'
                                            style="font-size: 16px;"></i></button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-danger" disabled title="Solicitar Acesso"><i
                                        class='far fa-trash-alt' style="font-size: 16px;"></i></button>
                            @endif
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            {{ $products->appends(['q' => $q ?? ('' ?? '')])->links() }}
        </div>

        <!-- MODAL ADD PRODUTOS -->
        @if (in_array('7', $user_permissions) || Auth::user()->is_admin)
            <div class="modal fade" id="modal_addproduto">
            @else
                <div class="modal fade" id="">
        @endif
        <div class="modal-dialog">
            <form method="post" action="{{ route('products.store') }}" id="form_add_produto">
                @csrf
                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">Adicionar Produto</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

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

                        <label for="name">Nome do Produto:</label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                            placeholder="Nome do Produto" id="name" value="{{ old('name') }}">

                        {{-- <label for="stock">Estoque Inicial:</label>
                        <input class="form-control @error('stock') is-invalid @enderror" type="text" name="stock"
                            placeholder="Sem estoque inicial" id="stock" value="{{ old('stock') }}" readonly> --}}
                        <input type="hidden" name="stock" value="0">

                        <label for="forecast">Previsão Média Diária de Produção:</label>
                        <input class="form-control @error('forecast') is-invalid @enderror" type="text" name="forecast"
                            placeholder="Previsão média diária" id="forecast" value="{{ old('forecast') }}">

                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer justify-content-between">
                        <input type="submit" class="btn btn-success" value="Salvar">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                    </div>

                </div>

            </form>
        </div>
        </div>

        <!-- MODAL EDIT PRODUTOS -->
        <div class="modal fade" id="modal_editproduto">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST"
                    action="{{ route('products.update', ['product' => $product->id ?? 1]) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-content">

                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Editar Produto</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

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

                            <label for="name">Nome do Produto:</label>
                            <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                                placeholder="Nome do Produto" id="edit_name" value="{{ $product->name ?? '' }}">

                            {{-- <label for="stock">Estoque Inicial:</label>
                            <input class="form-control @error('stock') is-invalid @enderror" type="text"
                                name="stock" placeholder="Estoque Inicial" id="edit_stock"
                                value="{{ $product->current_stock ?? 0 }}"> --}}
                            <input type="hidden" name="stock" value="0">

                            <label for="forecast">Previsão Média Diária de Produção:</label>
                            <input class="form-control @error('forecast') is-invalid @enderror" type="text"
                                name="forecast" placeholder="Previsão média diária" id="edit_forecast"
                                value="{{ $product->daily_production_forecast ?? '' }}">

                        </div>

                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../products'"
                                class="btn btn-danger" data-dismiss="modal">Fechar</button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

        <!-- MODAL ADD ESTOQUE -->
        <div class="modal fade" id="modal_addestoque">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST"
                    action="{{ route('products.update', ['product' => $product->id ?? 1]) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-content">

                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Adicionar Estoque</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

                        <!-- Modal body -->
                        <div class="modal-body">

                            <label for="name">Nome do Produto:</label>
                            <input class="form-control @error('name') is-invalid @enderror" type="text" name="name"
                                placeholder="Nome do Produto" id="edit_name" value="{{ $product->name ?? '' }}"
                                readonly>

                            <input required class="form-control mt-3 @error('dt_add_estoque') is-invalid @enderror"
                                type="date" name="dt_add_estoque" id="dt_add_estoque" value="{{ date('Y-m-d') }}">

                            <input required class="form-control mt-3 @error('add_estoque') is-invalid @enderror"
                                type="text" name="add_stock" placeholder="Produção" id="add_stock">
                        </div>

                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar" id="btn-add-estoque">
                            <button type="button" onclick="window.location.href = '../../products'"
                                class="btn btn-danger" data-dismiss="modal">Fechar</button>
                        </div>

                    </div>

                </form>
            </div>
        </div>

        <!-- MODAL ERROR -->
        <div class="modal fade" id="modal_error">
            <div class="modal-dialog">

                <div class="modal-content">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">ERRO!</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        Solicite Acesso!
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer justify-content-between">
                        <button type="button" onclick="window.location.href = '../../products'" class="btn btn-danger"
                            data-dismiss="modal">Fechar</button>
                    </div>

                </div>

                </form>
            </div>
        </div>

    </main>

@endsection

@section('js')

    <script>
        document.querySelector('#btn-add-estoque').addEventListener('click', function(e) {
            e.preventDefault();

            const inputAddStock = document.querySelector('#add_stock');
            const addStockValue = inputAddStock.value.trim(); // remove espaços extras

            // invalida se for vazio ou qualquer tipo de zero
            if (addStockValue === '' || Number(addStockValue) === 0) {
                inputAddStock.classList.add('is-invalid');
                inputAddStock.value = ''; // limpa o campo para forçar o placeholder aparecer
                inputAddStock.placeholder = 'Produção é um campo obrigatório.';
                return;
            }

            if (this.disabled) return; // evita duplo clique
            this.disabled = true;

            this.form.submit();
        });
    </script>

    @if ($errors->any() && !$errors->has('cannot_exclude') && !$errors->has('no-access'))
        <script>
            $(function() {
                $('#modal_addproduto').modal();
            })
        </script>
    @endif

    @if (!empty($product))
        @switch($action)
            @case('edit')
                <script>
                    $(function() {
                        $('#modal_editproduto').modal();
                    })
                </script>
            @break

            @case('add_estock')
                <script>
                    $(function() {
                        $('#modal_addestoque').modal();
                    })
                </script>
            @break

            @case('Não Autorizado')
                <script>
                    $(function() {
                        $('#modal_error').modal();
                    })
                </script>
            @break

            @default
        @endswitch
    @endif

    <script>
        $('#stock').mask('000.000.000', {
            reverse: true
        });
        $('#forecast').mask('000.000.000', {
            reverse: true
        });
        $('#edit_stock').mask('000.000.000', {
            reverse: true
        });
        $('#edit_forecast').mask('000.000.000', {
            reverse: true
        });
        $('#add_stock').mask('000.000.000', {
            reverse: true
        });
    </script>

@endsection
