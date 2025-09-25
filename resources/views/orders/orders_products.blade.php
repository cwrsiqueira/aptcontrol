@extends('layouts.template')

@section('title', 'Pedido')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Incluir Produtos</h2>
        <div class="col-md m-1 p-3" style="background-color:#eee;">
            <div class="row">
                <div class="col-md">
                    <label for="nome_produto">Produto</label>
                    <button class="btn btn-secondary btn-sm m-3" id="add_produto">Adicionar Produto</button>
                    <input class="form-control" type="text" name="nome_produto" id="nome_produto">
                    <input class="form-control" type="hidden" name="id_produto" id="id_produto">
                </div>
                <div class="col-md-3 mt-3">
                    <label for="quant_produto">Quantidade</label>
                    <input class="form-control mt-3" type="number" name="quant_produto" id="quant_produto">
                </div>
            </div>
        
            <div class="row">
                <div class="col-md">
                    <label for="situacao_retirada">Situação</label>
                    <select class="form-control" name="situacao_retirada" id="situacao_retirada">
                        <option value="nao_agendado">Não Agendado</option>
                        <option value="entregar">Entregar</option>
                        <option value="retirar">Retirar</option>
                    </select>
                </div>
                <div class="col-md">
                    <div class="data_retirada" style="display:none">
                        <label for="data_retirada">Data da Entrega</label>
                        <input class="form-control" type="date" name="data_retirada" id="data_retirada">
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection


