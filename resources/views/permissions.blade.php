@extends('layouts.template')

@section('title', 'Permissões')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h3>Permissões - <small>Clique na linha para editar</small></h3>

        <div class="list-group">
            @foreach ($users as $item)
                <a href="{{route('permissions.edit', ['permission' => $item['id']])}}" class="list-group-item list-group-item-action @if($item->confirmed_user === 0) list-group-item-danger @else list-group-item-success @endif">Usuário: {{$item->name}} - Permissão: {{$item->group_name}} - @if($item->confirmed_user === 0) Usuário não Autorizado @else Usuário Autorizado @endif</a>
            @endforeach
        </div>
        <div class="mt-3">
            {{$users->links()}}
        </div>

        <!-- MODAL EDIT CLIENTE -->
        <div class="modal fade" id="modal_editPermission">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'permissions.update', [ 'permission' => $user->id ?? 0 ] ) }}">
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
                            <input readonly class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Cliente" id="edit_name" value="{{$user['name'] ?? ''}}">
                            <input type="hidden" name="user_id" value="{{$user->id ?? ''}}">
            
                            <label for="contact">Permissões:</label>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    @foreach ($permissions ?? array() as $item)
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input @error($item->slug) is-invalid @enderror" value="{{$item->id}}" name="permission_item[]" id="{{$item->slug}}">{{$item->name}}
                                            </label>
                                        </div>
                                    @endforeach
                                </li>
                            </ul>

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../permissions'" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>
    </main>
@endsection

@section('js')
    @if (!empty($user))
        <script>
            $(function(){
                $('#modal_editPermission').modal();
            })
        </script>
    @endif
@endsection