@extends('layouts.template')

@section('title', 'Pedidos')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Adicionar Pedido</h2>

        <form action="{{route('orders.store')}}" method="post">
            @csrf
            <div class="row">

                <div class="col-md m-1 p-3" style="background-color:#eee;">
                    
                    <div class="row">
                        <div class="col-md">
                            <label for="data_pedido">Data do Pedido</label>
                            <input class="form-control" type="date" name="data_pedido" id="data_pedido" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md">
                            <label for="numero_pedido">Número do Pedido</label>
                            <input class="form-control" type="number" name="numero_pedido" id="numero_pedido">
                        </div>
                    </div>
            
                    <label for="nome_cliente">Cliente</label>
                    <input readonly class="form-control" type="search" name="nome_cliente" id="nome_cliente" value="{{$client['name']}}">
                    <input class="form-control" type="hidden" name="id_cliente" id="id_cliente" value="{{$client['id']}}">
                
                </div>

            </div>

            <input class="btn btn-success mt-3" type="submit" value="Criar Pedido">
        </form>
        
        <!-- The Modal -->
        <div class="modal fade" id="modal_addcliente">
            <div class="modal-dialog">
                <div class="modal-content">
    
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Adicionar Cliente</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
    
                <!-- Modal body -->
                <div class="modal-body">
                    <form method="post" id="form_add_cliente">
                        <input class="form-control mt-3" type="text" name="nome_cliente" placeholder="Nome do Cliente" id="input_cliente">
                        <input class="form-control mt-3" type="text" name="contato" placeholder="Telefone ou outro meio de contato">
                        <input class="form-control mt-3" type="text" name="endereco_completo" placeholder="Endereço Completo">
                    </form>
                </div>
    
                <!-- Modal footer -->
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-success" id="btn_add_cliente">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
    
                </div>
            </div>
        </div>
    
        <!-- The Modal -->
        <div class="modal fade" id="modal_addproduto">
            <div class="modal-dialog">
                <div class="modal-content">
    
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">Adicionar Produto</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
    
                <!-- Modal body -->
                <div class="modal-body">
                    <form method="post" id="form_add_produto">
                        <input class="form-control mt-3" type="text" name="nome_produto" placeholder="Nome do Produto" id="input_produto">
                        <input class="form-control mt-3" type="text" name="previsao" placeholder="Previsão média diária de Produção" id="previsao">
                        <input class="form-control mt-3" type="text" name="obs" placeholder="Observações">
                    </form>
                </div>
    
                <!-- Modal footer -->
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-success" id="btn_add_produto">Salvar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
    
                </div>
            </div>
        </div>

    </main>
@endsection

