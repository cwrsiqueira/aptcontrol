@extends('layouts.template')

@section('title', 'Log')

@section('content')

    <div class="modal" id="loader">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: transparent;border:0;">

            <!-- Modal body -->
            <div class="modal-body" style="text-align: center;">
                <div class="spinner-border" style="color: #fff;width:100px;height:100px;"></div>
                <p style="color: #fff;font-size:24px;font-weight:bold;">Aguarde...</p>
            </div>
            
            </div>
        </div>
    </div>

    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Log do Sistema</h2>

        <div class="card col-sm-4 m-3">
            <div class="card-header">
                Filtrar por:
            </div>
            <form method="get">
                <div class="card-body">
                    <select class="form-control" onchange="showLoader();this.form.submit();" name="acao" id="acao">
                        <option @empty($_GET['acao']) selected @endempty value="">Todos</option>
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
                {{-- Separa a data da hora pra pegar a data exata no campo Data/Hora --}}
                <?php $hora = explode(' ', $item['created_at']); ?>
                {{--  --}}
                <tr>
                    <td><?php echo $item['action']; ?></td>
                    <td><?php echo $item['item_id']; ?></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['menu']; ?></td>
                    <td><?php echo $item['name']; ?></td>

                    {{-- Condicional para ajustar a data, pois antes das 22 horas do dia 17/12/2020 a hora do sistema estava com 3 horas de diferença --}}
                    <?php if($item['created_at'] < '2020-12-17 22:00:00'): ?>
                    <td><?php echo date('d/m/Y - H:m:i', strtotime($item['created_at'].' -3 hours')); ?></td>
                    <?php else: ?>
                    {{-- Pega a data com date() e a hora sem o date, pra pegar a data exata --}}
                    <td><?php echo date('d/m/Y', strtotime($item['created_at'])).' - '.$hora[1]; ?></td>
                    {{--  --}}
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        {{$log->appends(['acao' => $_GET['acao'] ?? ''])->links()}}

    </main>
@endsection

@section('js')
    <script>
        function showLoader() {
            $('#loader').modal('show');
        }
        $(document).ready(function() {
            $('#loader').modal('hide');
        });
    </script>
@endsection

