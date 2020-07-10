@extends('layouts.template')

@section('title', 'Clientes')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">

        <h2>Clientes</h2>

        <div>
            <div class="d-flex justify-content-between">

                <button class="btn btn-secondary my-3" data-toggle="modal" data-target="#modal_addcliente">Cadastrar Cliente</button>

                <form method="get" class="d-flex align-items-center">
                    @if(!empty($q))
                    <a class="btn btn-sm btn-secondary m-3" href="{{route('clients.index')}}">Limpar Busca</a>
                    @endif
                    <input type="search" class=" form-control" name="q" id="q" placeholder="Procurar Cliente" value="{{$q ?? 0}}">
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
                        <th style="width: 60px;">#Ref.</th>
                        <th style="width: 500px;">Nome</th>
                        <th style="width: 130px;">Contato</th>
                        <th colspan="2" style="text-align:center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clients as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo $item['name']; ?></td>
                        <td><?php echo $item['contact']; ?></td>
                        <td style="width: 100px;"><a class="btn btn-sm btn-secondary" href="{{ route('clients.edit', ['client' => $item->id]) }}">Editar</a></td>
                        <td style="width: 100px;"><a class="btn btn-sm btn-secondary" href="{{ route('orders.create', ['client' => $item['id']]) }}">Efetuar Pedido</a></td>
                        <td><a class="btn btn-sm btn-secondary" href="{{ route('cc_client', ['id' => $item->id]) }}">C/C</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            {{$clients->appends(['q' => $q ?? ''])->links()}}
        </div>

        
        <!-- MODAL ADD CLIENTES -->
        <div class="modal fade" id="modal_addcliente">
            <div class="modal-dialog">
                <form method="post" action="{{route('clients.store')}}" id="form_add_cliente">
                    @csrf
                    <div class="modal-content">
            
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Adicionar Cliente</h4>
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
                                
                                <label for="name">Nome do Cliente:</label>
                                <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Cliente" id="name" value="{{old('name')}}">
                
                                <label for="contact">Contato:</label>
                                <input class="form-control @error('contact') is-invalid @enderror" type="text" name="contact" placeholder="Número de Contato" id="contact" value="{{old('contact')}}">
                
                                <label for="address">Endereço Completo:</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" type="text" name="address" placeholder="Endereço Completo" id="address">{{old('address')}}</textarea>

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

        <!-- MODAL EDIT CLIENTE -->
        <div class="modal fade" id="modal_editcliente">
            <div class="modal-dialog">
                <form class="form-horizontal" method="POST" action="{{ route( 'clients.update', [ 'client' => $client->id ?? 0 ] ) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-content">
            
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h4 class="modal-title">Editar Cliente</h4>
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
                                
                            <label for="name">Nome do Cliente:</label>
                            <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Nome do Cliente" id="edit_name" value="{{$client['name'] ?? ''}}">
            
                            <label for="contact">Contato:</label>
                            <input class="form-control @error('contact') is-invalid @enderror" type="text" name="contact" placeholder="Número de Contato" id="edit_contact" value="{{$client['contact'] ?? ''}}">
            
                            <label for="address">Endereço Completo:</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" type="text" name="address" placeholder="Endereço Completo" id="edit_address">{{$client['full_address'] ?? ''}}</textarea>

                        </div>
                
                        <!-- Modal footer -->
                        <div class="modal-footer justify-content-between">
                            <input type="submit" class="btn btn-success" value="Salvar">
                            <button type="button" onclick="window.location.href = '../../clients'" class="btn btn-danger" data-dismiss="modal">Close</button>
                        </div>
            
                    </div>
                
                </form>
            </div>
        </div>

    </main>

@endsection

@section('js')

    @if ($errors->any())
        <script>
            $(function(){
                $('#modal_addcliente').modal();
            })
        </script>
    @endif

    @if (!empty($client))
        <script>
            $(function(){
                $('#modal_editcliente').modal();
            })
        </script>
    @endif

    <script>
        // Mask Configurations
        var SPMaskBehavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00)00000-0000' : '(00)0000-00009';
        },
        spOptions = {
        onKeyPress: function(val, e, field, options) {
            field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };
        $('#contact').mask(SPMaskBehavior, spOptions);
        $('#edit_contact').mask(SPMaskBehavior, spOptions);
    </script>

@endsection