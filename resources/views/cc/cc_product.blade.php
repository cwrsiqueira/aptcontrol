@extends('layouts.estilos')

@section('title', 'Entregas por Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        {{-- Cabeçalho / ações --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-header">
            <h2 class="mb-0">Entregas por Produto</h2>
            <div class="btn-group">
                <a class="btn btn-sm btn-secondary" id="btn_voltar" href="{{ route('products.index') }}">
                    < Produtos</a>
                        <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
            </div>
        </div>

        <div class="row">
            {{-- RESUMO DO PRODUTO --}}
            <div class="col-lg">
                <div class="card card-lift mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold">Produto</span>
                        <span class="badge badge-mute badge-client-name">{{ $product->name }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-boxes mr-2"></i>
                            <strong class="mr-2">Estoque atual:</strong>
                            <span
                                class="@if ($total_sum - $quant_total != 0) font-weight-bold text-danger @endif">{{ number_format($product->current_stock ?? 0, 0, '', '.') }}</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="far fa-clipboard mr-2"></i>
                            <strong class="mr-2">Total a entregar:</strong>
                            <span
                                class="@if ($total_sum - $quant_total != 0) font-weight-bold text-danger @endif">{{ number_format($total_sum ?? 0, 0, '', '.') }}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="far fa-calendar-alt mr-2"></i>
                            <strong class="mr-2">Entregas a partir de:</strong>
                            <span>{{ date('d/m/Y', strtotime($delivery_in)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FILTROS --}}
            <div class="col-lg">
                <form method="get" action="{{ route('cc_product', ['id' => $product->id]) }}">
                    <div class="card card-lift mb-3">
                        <div class="card-header">
                            <strong>Filtros por legendas</strong>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="mb-0 d-flex align-items-center btn btn-sm btn-warning">
                                        <input class="mr-2" type="checkbox" name="por_favorito[]" value="1"
                                            @if (!empty($_GET['por_favorito']) && in_array(1, $_GET['por_favorito'])) checked @endif>
                                        <span class="text-truncate" title="Aguardando antecipação">A - Aguardando
                                            antecipação</span>
                                        <span
                                            class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[1] ?? 0, 0, '', '.') }}</span>
                                    </label>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="mb-0 d-flex align-items-center btn btn-sm btn-success">
                                        <input class="mr-2" type="checkbox" name="por_favorito[]" value="2"
                                            @if (!empty($_GET['por_favorito']) && in_array(2, $_GET['por_favorito'])) checked @endif>
                                        <span class="text-truncate" title="Liberados para entrega">L - Liberados para
                                            entrega</span>
                                        <span
                                            class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[2] ?? 0, 0, '', '.') }}</span>
                                    </label>
                                </div>
                            </div>
                            <hr>

                            <div class="d-flex align-items-center">
                                <input type="submit" value="Filtrar" id="search" class="btn btn-primary btn-sm">
                                <a href="{{ route('cc_product', ['id' => $product->id]) }}" id="clean_search"
                                    class="btn btn-outline-secondary btn-sm ml-2">Limpar Filtro</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABELA --}}
        <div class="card card-lift mb-5">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light sticky-header">
                        <tr>
                            {{-- <th>Data</th> --}}
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Categoria</th>
                            <th class="text-right">Saldo</th>
                            <th>Paletes</th>
                            <th>Vendedor</th>
                            <th>Data Entrega</th>
                            <th>Tipo Entrega</th>
                            <th class="nao-imprimir">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            @if ($item->saldo > 0)
                                <tr>
                                    {{-- DATA --}}
                                    {{-- <td>{{ date('d/m/Y', strtotime($item->order->order_date)) }}</td> --}}
                                    {{-- PEDIDO --}}
                                    <td>
                                        <a
                                            href="{{ route('order_products.index', ['order' => $item->order->id]) }}">#{{ $item->order_id }}</a>
                                    </td>
                                    {{-- CLIENTE --}}
                                    <td title="{{ $item->order->client->name }}"
                                        class="@if ($item->checkmark == 1) btn btn-sm btn-warning p-0 px-1 @elseif($item->checkmark == 2) btn btn-sm btn-success p-0 px-1 @endif mouse-help name-field">
                                        <div>
                                            <button
                                                class="btn btn-sm @if ($item->checkmark == 1) btn-warning @elseif($item->checkmark == 2) btn-success @endif mr-2 btn-legenda">
                                                @if ($item->checkmark == 1)
                                                    {{ 'A' }}
                                                @elseif($item->checkmark == 2)
                                                    {{ 'L' }}
                                                @endif
                                            </button>
                                            {{ Str::limit($item->order->client->name, 30, '...') }}
                                        </div>
                                    </td>
                                    {{-- CATEGORIA --}}
                                    <td>{{ $item->order->client->category->name }}</td>
                                    {{-- SALDO --}}
                                    <td class="text-right">
                                        {{ number_format($item->saldo > $item->quant ? $item->quant : $item->saldo, 0, '', '.') }}
                                    </td>
                                    {{-- CARGA --}}
                                    <td>
                                        <ul>
                                            @for ($i = 0; $i < 3; $i++)
                                                @if (isset($item->carga['tipo'][$i]) && $item->carga['tipo'][$i] != '')
                                                    <li>{{ $item->carga['tipo'][$i] ?? '' }} =
                                                        {{ $item->carga['quant'][$i] ?? '' }}</li>
                                                @endif
                                            @endfor
                                        </ul>
                                    </td>
                                    {{-- VENDEDOR --}}
                                    <td>{{ $item->order->seller->name ?? ' - ' }}</td>
                                    {{-- DATA DA ENTREGA --}}
                                    <td class="text-right d-flex flex-column align-items-end">
                                        {{ $item->delivery_date ? date('d/m/Y', strtotime($item->delivery_date)) : '—' }}
                                        <span
                                            class="btn btn-sm btn-danger p-0 px-1 @if (!$item->favorite_delivery) d-none @endif date-field">Data
                                            fixada</span>
                                    </td>
                                    {{-- TIPO DE ENTREGA --}}
                                    <td>
                                        @php $isCif = (Str::lower($item->order->withdraw) === 'entregar'); @endphp
                                        <span class="badge {{ $isCif ? 'badge-dark' : 'badge-info' }}">
                                            {{ Str::ucfirst($item->order->withdraw) }} ({{ $isCif ? 'CIF' : 'FOB' }})
                                        </span>
                                    </td>
                                    {{-- AÇÕES --}}
                                    <td class="nao-imprimir">
                                        @if (in_array('products.marcar_produto', $user_permissions) || Auth::user()->is_admin)
                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 1 ? '' : '-outline' }}-warning btn-fav"
                                                data-id="{{ $item->id }}"
                                                data-url="{{ route('products.marcar_produto', ['order_product' => $item, 'action' => 'checkmark', 'value' => 1]) }}"
                                                title="Marcar aguardando antecipação"><i
                                                    class="icon fas fa-clock"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 2 ? '' : '-outline' }}-success btn-fav"
                                                data-id="{{ $item->id }}"
                                                data-url="{{ route('products.marcar_produto', ['order_product' => $item, 'action' => 'checkmark', 'value' => 2]) }}"
                                                title="Marcar liberado para entrega"><i
                                                    class="icon fas fa-thumbs-up"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->favorite_delivery == 1 ? '' : '-outline' }}-danger btn-fav"
                                                data-id="{{ $item->id }}"
                                                data-url="{{ route('products.marcar_produto', ['order_product' => $item, 'action' => 'favorite_delivery', 'value' => 1]) }}"
                                                title="Marcar fixar data"><i class="icon fas fa-calendar-day"></i></button>
                                        @else
                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 1 ? '' : '-outline' }}-warning"
                                                title="Solicitar acesso" disabled><i class="icon fas fa-clock"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 2 ? '' : '-outline' }}-success"
                                                title="Solicitar acesso" disabled><i
                                                    class="icon fas fa-thumbs-up"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->favorite_delivery == 1 ? '' : '-outline' }}-danger"
                                                title="Solicitar acesso" disabled><i
                                                    class="icon fas fa-calendar-day"></i></button>
                                        @endif

                                        <button class="btn btn-sm btn-outline-secondary" disabled
                                            data-id="{{ $item->id }}" data-url="url" title="Adicionar observação"><i
                                                class="icon fas fa-comment-dots"></i></button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- <div class="card-footer">{{ $data->links() }}</div> --}}
        </div>

        <hr>

    </main>
@endsection

@section('css')
    <style>
        /* elevação sutil nos cards */
        .card-lift {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        }

        .meta-chip {
            display: inline-block;
            padding: .2rem .5rem;
            font-size: .95rem;
            font-weight: 600;
            border-radius: .375rem;
            background: #f1f3f5;
            color: #495057;
            line-height: 1.1;
        }

        .page-header h2 {
            font-weight: 600;
        }

        .badge-client-name {
            font-size: .85rem;
            padding: .25rem .5rem;
        }

        .mouse-help {
            cursor: help;
        }
    </style>
@endsection

@section('js')
    <script>
        // CSRF para Ajax
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        // Imprimir
        $('.btn-legenda').hide();
        $('#btn_imprimir').click(function() {
            $(this).hide();
            $('#btn_voltar, #btn_sair, #btn_recalc, #search, #clean_search, .nao-imprimir').hide();
            $('.btn-legenda').show();
            $('.name-field').attr('class', '');
            window.print();
            history.go(0);
        });

        var btnFav = document.querySelectorAll('.btn-fav');
        btnFav.forEach(item => {
            item.addEventListener('click', function() {
                const id = this.dataset.id;
                const url = this.dataset.url;

                $.post(url, {}, function(resp) {
                    if (resp && resp.ok) {
                        window.location.reload();
                    } else {
                        alert(resp.message || resp.error || 'Ocorreu um erro.');
                    }
                }, 'json').fail(function(xhr) {
                    if (xhr.status === 403) {
                        alert('Você não tem permissão para executar esta ação.');
                    } else if (xhr.status === 404) {
                        alert('Registro não encontrado.');
                    } else if (xhr.status === 422) {
                        alert(xhr.responseJSON?.message || 'Dados inválidos.');
                    } else {
                        alert('Erro no servidor. Tente novamente mais tarde.');
                    }

                    // console.error('Erro AJAX:', xhr.status, xhr.responseText);
                });
            })
        });
    </script>
@endsection
