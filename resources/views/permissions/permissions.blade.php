@extends('layouts.template')

@section('title', 'Permissões')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-5">
        {{-- Cabeçalho --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Permissões <small class="text-muted d-block d-sm-inline" style="font-size:14px;">(Clique na
                    linha para editar)</small>
            </h2>
        </div>

        {{-- Lista de usuários / permissões --}}
        <div class="card card-lift">
            <div class="card-header d-flex justify-content-between">
                <h4 class="mb-0">Usuários e status</h4>
                <small class="text-muted">Marcar como Admin <i class="fas fa-crown" style="color: orange"></i></small
                    class="text-muted">
            </div>

            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach ($users as $item)
                        @php
                            // mapeia status visual
                            $isAdmin = (int) $item->confirmed_user === 1;
                            $isDenied = (int) $item->confirmed_user === 0;
                            $statusTxt = $isDenied ? 'Usuário não Autorizado' : 'Usuário Autorizado';
                            $statusCls = $isDenied ? 'badge-danger' : 'badge-success';
                            // classes do item clicável (mantendo sua lógica original)
                            $linkClass = $isAdmin
                                ? 'list-group-item disabled'
                                : 'list-group-item list-group-item-action ' .
                                    ($isDenied ? 'list-group-item-danger' : 'list-group-item-success');
                        @endphp

                        <div class="row no-gutters align-items-center border-bottom">
                            <div class="col-sm-9">
                                <a href="{{ route('permissions.edit', ['permission' => $item['id']]) }}"
                                    class="{{ $linkClass }} d-flex align-items-center justify-content-between">
                                    <div class="pr-2">
                                        <div class="font-weight-semibold">Usuário: {{ $item->name }}</div>
                                        <div class="small text-muted">Permissão: {{ $isAdmin ? 'Admin' : 'Usuário' }}</div>
                                    </div>
                                    <div class="text-nowrap">
                                        <span class="badge {{ $statusCls }}">{{ $statusTxt }}</span>
                                    </div>
                                </a>
                            </div>

                            <div class="col-sm-3">
                                <div class="d-flex justify-content-end align-items-center pr-3 py-2">
                                    {{-- Alternar admin (mantém seu botão/código) --}}
                                    <button class="fas fa-crown update_admin mr-3" data-id="{{ $item['id'] }}"
                                        title="Tornar/Remover Admin"
                                        style="font-size:18px; cursor:pointer; border:0; background:transparent; {{ $isAdmin ? 'color:orange;' : 'color:#ccc;' }}">
                                    </button>

                                    {{-- Excluir (mantém sua lógica de bloqueio do admin) --}}
                                    <form class="m-0" action="{{ route('users.destroy', ['user' => $item['id']]) }}"
                                        method="POST"
                                        @if ($isAdmin) onsubmit="alert('Você não pode excluir o admin!');return false;" style="display:none;"
                                      @else
                                          onsubmit="return confirm('Confirma a exclusão do usuário?')" @endif>
                                        @csrf
                                        @method('DELETE')
                                        <button class="fas fa-trash-alt" title="Excluir usuário"
                                            style="font-size:18px; color:#d9534f; cursor:pointer; border:0; background:transparent;">
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($users->hasPages())
                <div class="card-footer">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- MODAL EDITAR PERMISSÕES --}}
        @if (isset($user_edit))
            <div class="modal fade" id="modal_editPermission">
                <div class="modal-dialog modal-lg">
                    <form class="form-horizontal" method="POST"
                        action="{{ route('permissions.update', ['permission' => $user_edit->id ?? 0]) }}">
                        @csrf
                        @method('PUT')

                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Permissões do Usuário</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>

                            <div class="modal-body" style="max-height:65vh; overflow:auto;">

                                {{-- Alerts de erro/sucesso --}}
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">x</button>
                                        <i class="icon fas fa-ban"></i> Erro!
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">x</button>
                                        <i class="icon fas fa-check"></i> {{ session('success') }}
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-sm">
                                        <div class="form-group">
                                            <label for="edit_name">Nome do Usuário:</label>
                                            <input readonly class="form-control @error('name') is-invalid @enderror"
                                                type="text" name="name" id="edit_name" placeholder="Nome do Cliente"
                                                value="{{ $user_edit['name'] ?? '' }}">
                                            <input type="hidden" name="user_id" value="{{ $user_edit->id ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-sm">
                                        <div class="form-group">
                                            <label for="edit_email">E-mail:</label>
                                            <input disabled class="form-control" type="text" name="email"
                                                id="edit_email" value="{{ $user_edit['email'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-sm d-flex justify-content-center align-items-center">
                                        <a href="{{ route('users.edit', ['user' => $user_edit]) }}"
                                            class="btn btn-sm btn-outline-secondary">Resetar senha</a>
                                    </div>
                                </div>

                                <div>
                                    <label class="font-weight-bold d-block mb-2">Permissões</label>

                                    @php

                                        // Opcional: array de slugs já atribuídos ao usuário (se o controller enviar)
                                        $user_perm_slugs = collect(old('perm', $user_permissions ?? []))->toArray();

                                        // Separa principais e subitens
                                        $primarias = $permissions->where('ident', 'pri')->values();
                                        $subsByGroup = $permissions->where('ident', 'sub')->groupBy(function ($p) {
                                            return Str::slug($p->group_name);
                                        });
                                    @endphp

                                    <ul class="list-group">
                                        @foreach ($primarias as $pri)
                                            @php
                                                // chave do grupo = último segmento do slug (ex.: "menu-produtos" → "produtos")
                                                $groupKey = Str::of($pri->slug)->explode('-')->slice(1)->implode('-');
                                                $children = $subsByGroup[$groupKey] ?? collect();
                                            @endphp

                                            <li class="list-group-item">
                                                {{-- Permissão principal (master) --}}
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input perm-master"
                                                        id="perm-{{ $pri->id }}" name="perm[]"
                                                        value="{{ $pri->slug }}" data-group="{{ $groupKey }}"
                                                        {{ in_array($pri->slug, $user_perm_slugs) ? 'checked' : '' }}>
                                                    <label class="custom-control-label font-weight-bold"
                                                        for="perm-{{ $pri->id }}">
                                                        {{ $pri->name }}
                                                    </label>
                                                </div>

                                                {{-- Subpermissões (filhas) --}}
                                                @if ($children->isNotEmpty())
                                                    <div class="pl-4">
                                                        @foreach ($children as $sub)
                                                            <div class="custom-control custom-checkbox mb-1">
                                                                <input type="checkbox"
                                                                    class="custom-control-input perm-child perm-child-{{ $groupKey }}"
                                                                    id="perm-{{ $sub->id }}" name="perm[]"
                                                                    value="{{ $sub->slug }}"
                                                                    {{ in_array($sub->slug, $user_perm_slugs) ? 'checked' : '' }}>
                                                                <label class="custom-control-label"
                                                                    for="perm-{{ $sub->id }}">
                                                                    {{ $sub->name }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                            </div>

                            <div class="modal-footer justify-content-between">
                                <input type="submit" class="btn btn-success" value="Salvar">
                                <button type="button" onclick="window.location.href='../../permissions'"
                                    class="btn btn-danger" data-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </main>
@endsection

@section('css')
    <style>
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .page-header h2 {
            font-weight: 600;
        }

        .font-weight-semibold {
            font-weight: 600;
        }

        /* hover mais suave no list-group */
        .list-group-item-action:hover {
            background: #f8f9fa;
        }

        /* bordas entre linhas sem ficarem muito fortes */
        .list-group-flush .list-group-item {
            border-left: 0;
            border-right: 0;
        }
    </style>
@endsection

@section('js')
    <script>
        $(function() {
            $('.update_admin').click(function() {
                var id = $(this).attr('data-id');
                var user_id = "{{ $user['id'] }}";
                if (id != user_id) {
                    $.ajax({
                        url: "{{ route('update_admin') }}",
                        type: "get",
                        data: {
                            id: id
                        },
                        success: function() {
                            location.reload();
                        }
                    });
                } else {
                    alert('Você não pode tirar seu próprio acesso!');
                }
            });
        });
    </script>

    @if (!empty($user_edit))
        <script>
            $(function() {
                $('#modal_editPermission').modal();
            });
        </script>
    @endif
@endsection
