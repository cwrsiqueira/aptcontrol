@extends('layouts.template')

@section('title', 'Categorias')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <div class="row">
            <div class="col-sm">
                <h2>Categorias de Clientes</h2>
            </div>
            <div class="col-sm" style="text-align: right">
                <a href="{{route('clients.index')}}">Clientes</a> / Categorias
            </div>
        </div>

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

                <button @if(in_array('21', $user_permissions) || Auth::user()->confirmed_user === 1) @else disabled title="Solicitar Acesso" @endif class="btn btn-secondary my-3" data-toggle="modal" data-target="#modal_addcategoria">Cadastrar Categoria</button>

            </div>
            
            <table class="table" style="text-align: center">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th colspan="2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $item): ?>
                    <tr>
                        <td><?php echo $item['name']; ?></td>
                        <td>
                            @if(in_array('22', $user_permissions) || Auth::user()->confirmed_user === 1) 
                            <a class="btn btn-sm btn-secondary" href="{{ route('categories.edit', [ 'category' => $item->id ] ) }}">Editar</a>
                            @else 
                            <button class="btn btn-sm btn-secondary" disabled title="Solicitar Acesso">Editar</button>
                            @endif
                        </td>
                        <td>
                            @if(in_array('23', $user_permissions) || Auth::user()->confirmed_user === 1) 
                            <form title="Excluir" action=" {{ route('categories.destroy', [ 'category' => $item->id ] ) }} " method="POST" onsubmit="return confirm('Confirma a exclusão da categoria?')" >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class='far fa-trash-alt' style="font-size: 16px;"></i></button>
                            </form>
                            @else 
                            <button class="btn btn-sm btn-danger" disabled title="Solicitar Acesso"><i class='far fa-trash-alt' style="font-size: 16px;"></i></button>
                            @endif
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            {{$categories->links()}}
        </div>

        
        <!-- MODAL ADD CATEGORIAS -->
        <div class="modal fade" id="modal_addcategoria">
            <div class="modal-dialog">
                <form method="post" action="{{route('categories.store')}}" id="form_add_categoria">
                    @csrf
                    <div class="modal-content">
            
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Adicionar Categoria</h4>
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
                                
                                <label for="name">Nome da Categoria:</label>
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome da Categoria" id="name" value="{{old('name')}}">

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

        <!-- MODAL EDIT CATEGORIAS -->
        <div class="modal fade" id="modal_editcategoria">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'categories.update', [ 'category' => $category->id ?? 0 ] ) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-content">
            
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Editar Categoria</h4>
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
                                
                            <label for="name">Nome da Categoria:</label>
                            <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome da Categoria" id="edit_name" value="{{$category['name'] ?? ''}}">

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="javascript:history.go(-1);" class="btn btn-danger" data-dismiss="modal">Close</button>
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
                $('#modal_addcategoria').modal();
            })
        </script>
    @endif

    @if (!empty($category))
        <script>
            $(function(){
                $('#modal_editcategoria').modal();
            })
        </script>
    @endif

@endsection