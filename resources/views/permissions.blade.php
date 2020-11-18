@extends('layouts.template')

@section('title', 'Permissões')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h3>Permissões - <small>Clique na linha para editar</small></h3>

        <div class="list-group">
            @foreach ($users as $item)
            <div class="row mb-1">
                <div class="col-sm-8">
                    <a href="{{route('permissions.edit', ['permission' => $item['id']])}}" class="@if($item->confirmed_user === 1)list-group-item disabled @elseif($item->confirmed_user === 0) list-group-item list-group-item-action list-group-item-danger @else list-group-item list-group-item-action list-group-item-success @endif">Usuário: {{$item->name}} - Permissão: {{$item->group_name}} - @if($item->confirmed_user === 0) Usuário não Autorizado @else Usuário Autorizado @endif</a>
                </div>
                <div class="">
                    <div class="p-3">
                        <button class='fas fa-crown update_admin' data-id="{{$item['id']}}" style='font-size:18px;cursor: pointer;border:0; background-color:transparent;@if($item->confirmed_user === 1) color:orange; @else color:#ccc; @endif'></button>
                    </div>
                </div>
                <div class="">
                    <form class="p-3" action=" {{ route('users.destroy', [ 'user' => $item['id'] ]) }} " method="POST" 
                        @if ($item->confirmed_user === 1)
                            onsubmit="alert('Você não pode excluir o admin!');return false;"
                            style="display:none;"
                        @else
                            onsubmit="return confirm('Confirma a exclusão do usuário?')"
                        @endif 
                    >
                        @csrf
                        @method('DELETE')
                        <button class='fas fa-trash-alt' style='font-size:18px;color:red;cursor: pointer;border:0; background-color:transparent;'></button>
                    </form>
                </div>
            </div>
                
                
            @endforeach
        </div>
        <div class="mt-3">
            {{$users->links()}}
        </div>

        <!-- MODAL EDIT CLIENTE -->
        <div class="modal fade" id="modal_editPermission">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'permissions.update', [ 'permission' => $user_edit->id ?? 0 ] ) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-content">
            
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Editar Permissões do Usuário</h4>
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
                                
                            <label for="name">Nome do Usuário:</label>
                            <input readonly class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Cliente" id="edit_name" value="{{$user_edit['name'] ?? ''}}">
                            <input type="hidden" name="user_id" value="{{$user_edit->id ?? ''}}">
            
                            <label for="contact">Permissões:</label>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    @foreach ($permissions ?? array() as $item)
                                        <div class="form-check @if($item->ident == 'sub') ml-3 @endif">
                                            <label class="form-check-label">
                                                <input @if(in_array($item->id, $user_permissions)) checked @endif type="checkbox" class="form-check-input @error($item->slug) is-invalid @enderror" value="{{$item->id}}" name="permission_item[]" id="{{$item->slug}}">{{$item->name}}
                                            </label>
                                        </div>
                                    @endforeach
                                </li>
                            </ul>

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../permissions'" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>
    </main>
@endsection

@section('js')

    <script>
        $(function(){
            $('.update_admin').click(function(){
                let id = $(this).attr('data-id');
                let user_id = "{{$user['id']}}";
                if (id != user_id) {
                    $.ajax({
                        url:"{{route('update_admin')}}",
                        type:"get",
                        data:{id:id},
                        success:function(){
                            location.reload();
                        }
                    })
                } else {
                    alert('Você não pode tirar seu próprio acesso!');
                }
            })
        })
    </script>

    @if (!empty($user_edit))
        <script>
            $(function(){
                $('#modal_editPermission').modal();
            })
        </script>
    @endif

@endsection