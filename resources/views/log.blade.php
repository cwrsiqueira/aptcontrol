@extends('layouts.template')

@section('title', 'Log')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Log do Sistema</h2>

        <div class="card col-sm-4 m-3">
            <div class="card-header">
                Filtrar por:
            </div>
            <form method="get">
                <div class="card-body">
                    <select class="form-control" onchange="this.form.submit();" name="acao" id="acao">
                        <option @if(!empty($_GET['acao']) && $_GET['acao'] == 'Cadastro') selected @endif>Cadastro</option>
                        <option @if(!empty($_GET['acao']) && $_GET['acao'] == 'Alteração') selected @endif>Alteração</option>
                        <option @if(!empty($_GET['acao']) && $_GET['acao'] == 'Cancelamento') selected @endif>Cancelamento</option>
                        <option @if(!empty($_GET['acao']) && $_GET['acao'] == 'Registro de Entrega') selected @endif>Registro de Entrega</option>
                    </select>
                </div>
            </form>
        </div>

        <table class="table" style="text-align: center">
            <thead>
                <tr>
                    <th>Ação</th>
                    <th>Item</th>
                    <th>Nome</th>
                    <th>Menu</th>
                    <th>Usuário</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($log as $item): ?>
                <tr>
                    <td><?php echo $item['action']; ?></td>
                    <td><?php echo $item['item_id']; ?></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['menu']; ?></td>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo date('d/m/Y - H:m:i', strtotime($item['created_at'].'-3 hours')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        {{$log->links()}}

    </main>
@endsection

