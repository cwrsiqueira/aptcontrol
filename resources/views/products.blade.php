@extends('layouts.template')

@section('title', 'Produtos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Produtos</h2>

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

        <div>
            <div class="d-flex justify-content-between">

                <button class="btn btn-secondary my-3" data-toggle="modal" data-target="#modal_addproduto" @if(in_array('7', $user_permissions) || Auth::user()->confirmed_user === 1) @else style="visibility:hidden;" @endif>Cadastrar Produto</button>

                <form method="get" class="d-flex align-items-center">
                    @if(!empty($q ?? ''))
                    <a class="btn btn-sm btn-secondary m-3" href="{{route('products.index')}}">Limpar Busca</a>
                    @endif
                    <input type="search" class=" form-control" name="q" id="q" placeholder="Procurar Produto" value="{{$q ?? ''}}">
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
                        <th>Estoque</th>
                        <th>Previsão Diária</th>
                        <th colspan="4" style="text-align:center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo $item['name']; ?></td>
                        <td><?php echo number_format($item['current_stock'], 0, '', '.'); ?></td>
                        <td><?php echo number_format($item['daily_production_forecast'], 0, '', '.'); ?></td>
                        <td @if(in_array('8', $user_permissions) || Auth::user()->confirmed_user === 1) @else style="visibility:hidden;" @endif>
                            <a class="btn btn-sm btn-secondary" href="{{ route('products.edit', ['product' => $item->id, 'action' => 'edit']) }}">Editar</a>
                        </td>
                        <td @if(in_array('9', $user_permissions) || Auth::user()->confirmed_user === 1) @else style="visibility:hidden;" @endif>
                            <a class="btn btn-sm btn-secondary" href="{{ route('products.edit', ['product' => $item->id, 'action' => 'add_estock']) }}">+ Estoque</a>
                        </td>
                        <td @if(in_array('10', $user_permissions) || Auth::user()->confirmed_user === 1) @else style="visibility:hidden;" @endif>
                            <a class="btn btn-sm btn-secondary" href="{{ route('cc_product', ['id' => $item->id]) }}">C/C</a>
                        </td>
                        <td @if(in_array('11', $user_permissions) || Auth::user()->confirmed_user === 1) @else style="visibility:hidden;" @endif>
                            <form title="Excluir" action=" {{ route('products.destroy', [ 'product' => $item->id ]) }} " method="POST" onsubmit="return confirm('Confirma a exclusão do produto?')" >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class='far fa-trash-alt' style="font-size: 16px;"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            {{$products->appends(['q' => $q ?? '' ?? ''])->links()}}
        </div>

        
        <!-- MODAL ADD PRODUTOS -->
        <div class="modal fade" id="modal_addproduto">
            <div class="modal-dialog">
                <form method="post" action="{{route('products.store')}}" id="form_add_produto">
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
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Produto" id="name" value="{{old('name')}}">
                
                                <label for="stock">Estoque Inicial:</label>
                                <input class="form-control @error('stock') is-invalid @enderror" type="text" name="stock" placeholder="Estoque Inicial" id="stock" value="{{old('stock')}}">
                
                                <label for="forecast">Previsão Média Diária de Produção:</label>
                                <input class="form-control @error('forecast') is-invalid @enderror" type="text" name="forecast" placeholder="Previsão média diária" id="forecast" value="{{old('forecast')}}">

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>

        <!-- MODAL EDIT PRODUTOS -->
        <div class="modal fade" id="modal_editproduto">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'products.update', [ 'product' => $product->id ?? 1 ] ) }}">
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
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Produto" id="edit_name" value="{{$product->name ?? ''}}">
                
                                <label for="stock">Estoque Inicial:</label>
                                <input class="form-control @error('stock') is-invalid @enderror" type="text" name="stock" placeholder="Estoque Inicial" id="edit_stock" value="{{$product->current_stock ?? 0}}">
                
                                <label for="forecast">Previsão Média Diária de Produção:</label>
                                <input class="form-control @error('forecast') is-invalid @enderror" type="text" name="forecast" placeholder="Previsão média diária" id="edit_forecast" value="{{$product->daily_production_forecast ?? ''}}">

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../products'" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>
        
        <!-- MODAL ADD ESTOQUE -->
        <div class="modal fade" id="modal_addestoque">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'products.update', [ 'product' => $product->id ?? 1 ] ) }}">
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
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Produto" id="edit_name" value="{{$product->name ?? ''}}">
                
                                <input required class="form-control mt-3 @error('dt_add_estoque') is-invalid @enderror" type="date" name="dt_add_estoque" id="dt_add_estoque">
                
                                <input required class="form-control mt-3 @error('add_estoque') is-invalid @enderror" type="text" name="add_stock" placeholder="Produção" id="add_stock">
                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../products'" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>
    </main>

@endsection

@section('js')

    @if ($errors->any() && !$errors->has('cannot_exclude') && !$errors->has('no-access'))
        <script>
            $(function(){
                $('#modal_addproduto').modal();
            })
        </script>
    @endif

    @if (!empty($product))

        @if ($action === 'edit')
            <script>
                $(function(){
                    $('#modal_editproduto').modal();
                })
            </script>
        @else
            <script>
                $(function(){
                    $('#modal_addestoque').modal();
                })
            </script>
        @endif
        
    @endif

    <script>
        $('#stock').mask('000.000.000', {reverse:true});
        $('#forecast').mask('000.000.000', {reverse:true});
        $('#edit_stock').mask('000.000.000', {reverse:true});
        $('#edit_forecast').mask('000.000.000', {reverse:true});
        $('#add_stock').mask('000.000.000', {reverse:true});
    </script>

@endsection