@extends('layouts.template')

@section('title', 'Log')

@section('content')

    {{-- Loader --}}
    <div class="modal" id="loader" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: transparent; border:0;">
                <div class="modal-body text-center">
                    <div class="spinner-border" style="color:#fff; width:100px; height:100px;"></div>
                    <p style="color:#fff; font-size:24px; font-weight:bold;">Aguarde...</p>
                </div>
            </div>
        </div>
    </div>

    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-3">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-3 page-header">
            <h2 class="mb-0">Log do Sistema</h2>
        </div>

        <div class="row">
            {{-- Filtro --}}
            <div class="col-md-5">
                <div class="card card-lift mb-3">
                    <div class="card-header">
                        <strong>Filtrar por</strong>
                    </div>
                    <form method="get">
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="acao" class="mb-1">Ação</label>
                                <select class="form-control" onchange="showLoader(); this.form.submit();" name="acao"
                                    id="acao">
                                    <option @empty($_GET['acao']) selected @endempty value="">Todos
                                    </option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Cadastro') selected @endif>Cadastro</option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Alteração') selected @endif>Alteração</option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Deleção') selected @endif>Deleção</option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Cancelamento') selected @endif>Cancelamento</option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Registro de Entrega') selected @endif>
                                        Registro de Entrega
                                    </option>
                                    <option @if (!empty($_GET['acao']) && $_GET['acao'] == 'Permissão') selected @endif>Permissão</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="card card-lift">
            <div class="table-responsive tableFixHead">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="thead-light sticky-header">
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
                        @foreach ($log as $item)
                            @php
                                $actionMap = [
                                    'Cadastro' => 'badge-success',
                                    'Alteração' => 'badge-warning',
                                    'Deleção' => 'badge-danger',
                                    'Cancelamento' => 'badge-danger',
                                    'Registro de Entrega' => 'badge-info',
                                ];
                                $badgeCls = $actionMap[$item['action']] ?? 'badge-secondary';
                                $hora = explode(' ', $item['created_at']);
                            @endphp
                            <tr>
                                <td><span class="badge {{ $badgeCls }}">{{ $item['action'] }}</span></td>
                                <td>{{ $item['item_id'] }}</td>
                                <td>{{ $item['item_name'] }}</td>
                                <td>{{ $item['menu'] }}</td>
                                <td>{{ $item['name'] }}</td>

                                {{-- Ajuste histórico de fuso (mantido do seu código) --}}
                                @if ($item['created_at'] < '2020-12-17 22:00:00')
                                    <td>{{ date('d/m/Y - H:m:i', strtotime($item['created_at'] . ' -3 hours')) }}</td>
                                @else
                                    <td>{{ date('d/m/Y', strtotime($item['created_at'])) . ' - ' . $hora[1] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($log->hasPages())
                <div class="card-footer">
                    {{ $log->appends(['acao' => $_GET['acao'] ?? ''])->links() }}
                </div>
            @endif
        </div>
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

        /* Cabeçalho fixo na tabela */
        .tableFixHead {
            max-height: 65vh;
            overflow-y: auto;
        }

        .tableFixHead .sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
    </style>
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
