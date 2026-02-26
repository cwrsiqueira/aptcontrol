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
                                <label class="btn btn-sm btn-warning w-100 text-left">
                                    <input class="mr-2" type="checkbox" name="por_favorito[]" value="1"
                                        @if (!empty($_GET['por_favorito']) && in_array(1, $_GET['por_favorito'])) checked @endif onclick="this.form.submit()">
                                    <span class="" title="Aguardando antecipação">A - Aguardando
                                        antecipação</span>
                                    <span
                                        class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[1] ?? 0, 0, '', '.') }}</span>
                                </label>
                                <label class="btn btn-sm btn-success w-100 text-left">
                                    <input class="mr-2" type="checkbox" name="por_favorito[]" value="2"
                                        @if (!empty($_GET['por_favorito']) && in_array(2, $_GET['por_favorito'])) checked @endif onclick="this.form.submit()">
                                    <span class="" title="Liberados para entrega">L - Liberados para
                                        entrega</span>
                                    <span
                                        class="ml-2 badge badge-light">{{ number_format($quant_por_favorito[2] ?? 0, 0, '', '.') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- MONTAR CARGAS (linha separada, largura total) --}}
        <div class="row">
            <div class="col-12">
                <div class="card card-lift mb-3">
                    <div class="card-header">
                        <strong>Montar cargas</strong>
                    </div>

                    <div class="card-body">
                        <form method="get" action="{{ route('cc_product', ['id' => $product->id]) }}">
                            <div class="row">
                                <div class="col-md mb-2">
                                    <label class="btn btn-sm btn-secondary w-100 text-left">
                                        <input class="mr-2" type="checkbox" name="entregar" value="1"
                                            @if (!empty($_GET['entregar']) && $_GET['entregar'] == 1) checked @endif onclick="this.form.submit()">
                                        <span class="text-truncate" title="Pedidos para entrega">Entregar (CIF)</span>
                                        {{-- <span
                                            class="ml-2 badge badge-light">{{ number_format($quant_por_entregar[1] ?? 0, 0, '', '.') }}</span> --}}
                                    </label>
                                </div>
                            </div>
                        </form>

                        @if (($cargas_por_caminhao ?? collect())->count())
                                <div class="card mb-3 shadow-sm">
                                    <div class="card-header">
                                        <strong>Cargas por caminhão</strong>
                                    </div>
                                    <div class="card-body py-2">
                                        @foreach ($cargas_por_caminhao as $truckId => $loads)
                                            @php $truck = $loads->first()->truck; @endphp
                                            <div class="border-bottom py-2">
                                                <div class="fw-bold mb-1">
                                                    <i class="fa fa-truck"></i> {{ $truck->modelo ?? 'Caminhão' }}
                                                    @if ($truck->placa)
                                                        <span class="text-muted">({{ $truck->placa }})</span>
                                                    @endif
                                                </div>
                                                @foreach ($loads as $cargaIdx => $load)
                                                    @php
                                                        $totalPalLoad = $load->items->sum('qtd_paletes');
                                                        $capacidade = $load->truck->capacidade_paletes ?? 0;
                                                        $badgePaletes = $totalPalLoad . '/' . $capacidade;
                                                        $badgeCor = $totalPalLoad <= 0 ? 'success' : ($totalPalLoad >= $capacidade ? 'danger' : 'warning');
                                                        $itensPorZona = $load->items->groupBy(fn ($i) => $i->zone_id ? ($i->zone->nome ?? '') : ($i->zona_nome ?? 'SEM ZONA'));
                                                    @endphp
                                                    <div class="ms-3 mb-2">
                                                        <span class="badge badge-info">Carga {{ $cargaIdx + 1 }}</span>
                                                        @if ($load->motorista)
                                                            <span class="text-muted small">Motorista: {{ $load->motorista }}</span>
                                                        @endif
                                                        <span class="badge badge-{{ $badgeCor }} ml-1" title="{{ $badgePaletes }} paletes ({{ $totalPalLoad <= 0 ? 'vazio' : ($totalPalLoad >= $capacidade ? 'cheio' : 'em andamento') }})">{{ $badgePaletes }} pal.</span>
                                                        <a href="{{ route('cc.carga_load_pdf', ['load' => $load->id]) }}"
                                                            class="btn btn-sm btn-outline-danger ml-1" target="_blank"
                                                            title="Gerar PDF"><i class="fa fa-file-pdf"></i></a>
                                                        <form method="POST" action="{{ route('cc.carga_load_limpar', ['load' => $load]) }}"
                                                            class="d-inline ml-1"
                                                            onsubmit="return confirm('Limpar esta carga?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Limpar carga"><i class="fa fa-trash"></i></button>
                                                        </form>
                                                        <div class="small mt-1">
                                                            @foreach ($itensPorZona as $zonaNome => $itensZona)
                                                                <div class="mb-1">
                                                                    <strong>Zona {{ strtoupper($zonaNome ?: 'SEM ZONA') }}:</strong>
                                                                    @foreach ($itensZona->groupBy('order_product_id') as $opId => $itens)
                                                                        @php
                                                                            $first = $itens->first();
                                                                            $op = $first->orderProduct;
                                                                            $prodNome = $op->product->name ?? 'Sem produto';
                                                                            $somaPal = $itens->sum('qtd_paletes');
                                                                            $somaProd = $itens->sum(fn ($i) => $i->orderProduct->quant ?? 0);
                                                                        @endphp
                                                                        <span class="d-inline-flex align-items-center">
                                                                            {{ $prodNome }} ({{ number_format($somaProd, 0, ',', '.') }} prod. / {{ $somaPal }} pal.)
                                                                            <form method="POST" action="{{ route('cc.carga_load_remover', ['load' => $load, 'order_product' => $op->id]) }}"
                                                                                class="d-inline ml-1" onsubmit="return confirm('Remover este pedido da carga?');">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remover da carga"><i class="fa fa-times-circle"></i></button>
                                                                            </form>
                                                                        </span>
                                                                        @if (!$loop->last) | @endif
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
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
                                    {{-- CARGA / PALETES --}}
                                    <td>
                                        <ul>
                                            @for ($i = 0; $i < 3; $i++)
                                                @if (isset($item->carga['tipo'][$i]) && $item->carga['tipo'][$i] != '')
                                                    <li>{{ $item->carga['tipo'][$i] ?? '' }} =
                                                        {{ $item->carga['quant'][$i] ?? '' }}</li>
                                                @endif
                                            @endfor
                                        </ul>
                                        @if (($item->paletes_total ?? 0) > 0 && $item->order->withdraw === 'entregar')
                                            @php
                                                $usado = $item->paletes_em_carga ?? 0;
                                                $total = $item->paletes_total ?? 0;
                                                $restante = $total - $usado;
                                            @endphp
                                            <span class="badge badge-{{ $restante <= 0 ? 'success' : ($usado > 0 ? 'warning' : 'light') }}" title="Paletes em carga">
                                                {{ $usado }} de {{ $total }} em carga
                                            </span>
                                        @endif
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
                                        <div
                                            class="@if ($item->order->withdraw !== 'entregar') d-none @else d-flex @endif flex-column">
                                            <span class="text-muted">Bairro:</span>
                                            {{ Str::ucfirst($item->order->bairro) ?? ' - ' }}
                                            <span class="text-muted">Zona:</span>
                                            {{ Str::ucfirst($item->order->zona) ?? ' - ' }}
                                        </div>
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
                                                title="Marcar fixar data"><i
                                                    class="icon fas fa-calendar-day"></i></button>

                                            @if ($item->order->withdraw === 'entregar')
                                                @php
                                                    $totalPaletesItem = $item->paletes_total ?? \App\Helpers\Helper::cargaTotalPaletes($item->carga);
                                                    $paletesEmCarga = $item->paletes_em_carga ?? 0;
                                                    $podeAdicionar = $paletesEmCarga < $totalPaletesItem;
                                                    $paletesDisponivel = $totalPaletesItem - $paletesEmCarga;
                                                @endphp
                                                <button type="button" class="btn btn-sm {{ $item->em_carga ? 'btn-secondary' : 'btn-outline-secondary' }} btn-add-carga {{ !$podeAdicionar ? 'disabled' : '' }}"
                                                    title="{{ $podeAdicionar ? 'Adicionar à Carga' : 'Todos os paletes já estão em carga' }}"
                                                    data-order-product-id="{{ $item->id }}"
                                                    data-max-paletes="{{ $paletesDisponivel }}"
                                                    data-bairro="{{ $item->order->bairro ?? '' }}"
                                                    data-zona="{{ $item->order->zona ?? '' }}">
                                                    <i class="fa fa-truck"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    title="Só é possível marcar carga para pedidos CIF" disabled><i
                                                        class="icon fas fa-truck"></i></button>
                                            @endif
                                        @else
                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 1 ? '' : '-outline' }}-warning"
                                                title="Solicitar acesso" disabled><i
                                                    class="icon fas fa-clock"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->checkmark == 2 ? '' : '-outline' }}-success"
                                                title="Solicitar acesso" disabled><i
                                                    class="icon fas fa-thumbs-up"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->favorite_delivery == 1 ? '' : '-outline' }}-danger"
                                                title="Solicitar acesso" disabled><i
                                                    class="icon fas fa-calendar-day"></i></button>

                                            <button
                                                class="btn btn-sm btn{{ $item->marcado_carga == 1 ? '' : '-outline' }}-secondary"
                                                title="Adicionar à Carga" disabled><i
                                                    class="icon fas fa-truck"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- <div class="card-footer">{{ $data->links() }}</div> --}}
        </div>

        {{-- Modal Adicionar à Carga --}}
        <div class="modal fade" id="modalAddCarga" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Adicionar à Carga</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form id="formAddCarga">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="order_product_id" id="addCargaOrderProductId">
                            <div class="form-group">
                                <label for="addCargaTruck">Caminhão *</label>
                                <select class="form-control" name="truck_id" id="addCargaTruck" required>
                                    <option value="">Selecione...</option>
                                    @foreach ($trucks ?? [] as $t)
                                        <option value="{{ $t->id }}">{{ $t->modelo ?? 'Caminhão' }}{{ $t->placa ? ' - ' . $t->placa : '' }} ({{ $t->capacidade_paletes }} paletes)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="addCargaMotorista">Motorista</label>
                                <input type="text" class="form-control" name="motorista" id="addCargaMotorista"
                                    maxlength="150" placeholder="Motorista que fará a entrega">
                            </div>
                            <div class="form-group">
                                <label for="addCargaZone">Zona</label>
                                <select class="form-control" name="zone_id" id="addCargaZone">
                                    <option value="">Selecione ou use zona do pedido</option>
                                    @foreach ($zones ?? [] as $z)
                                        @php
                                            $bairrosStr = $z->bairros->pluck('bairro_nome')->implode(', ');
                                        @endphp
                                        <option value="{{ $z->id }}">{{ $bairrosStr ? $bairrosStr . ' — ' : '' }}{{ $z->nome }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="zona_nome" id="addCargaZonaNome">
                            </div>
                            <div class="form-group">
                                <label for="addCargaQtd">Quantidade de paletes *</label>
                                <input type="number" class="form-control" name="qtd_paletes" id="addCargaQtd" min="1" required>
                                <small class="form-text text-muted">Máximo: <span id="addCargaMaxPaletes">0</span></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar</button>
                        </div>
                    </form>
                </div>
            </div>
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

        #addCargaZone {
            font-size: 0.875rem;
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
    <script>
        // salva a posição antes de enviar qualquer form
        document.addEventListener('submit', function(e) {
            if (e.target.id !== 'formAddCarga') {
                sessionStorage.setItem('scrollY', window.scrollY);
            }
        });

        // restaura a posição após reload
        document.addEventListener('DOMContentLoaded', function() {
            const y = sessionStorage.getItem('scrollY');
            if (y !== null) {
                window.scrollTo(0, parseInt(y, 10));
                sessionStorage.removeItem('scrollY');
            }
        });

        // Modal Adicionar à Carga
        $('.btn-add-carga').on('click', function() {
            const id = $(this).data('order-product-id');
            const max = parseInt($(this).data('max-paletes'), 10) || 0;
            const zona = $(this).data('zona') || '';
            if (max <= 0) {
                alert('Todos os paletes já estão em carga ou não existem paletes cadastrados para o pedido.');
                return;
            }
            $('#addCargaOrderProductId').val(id);
            $('#addCargaMaxPaletes').text(max);
            $('#addCargaQtd').attr('max', max).val(max);
            $('#addCargaZonaNome').val(zona);
            $('#addCargaZone').val('');
            $('#modalAddCarga').modal('show');
        });

        $('#formAddCarga').on('submit', function(e) {
            e.preventDefault();
            const zoneId = $('#addCargaZone').val();
            if (!zoneId) {
                $('#addCargaZonaNome').val($('#addCargaZonaNome').val() || 'SEM ZONA');
            } else {
                $('#addCargaZonaNome').val('');
            }
            $.post('{{ route("cc.add_to_load") }}', $(this).serialize(), function(resp) {
                if (resp && resp.ok) {
                    $('#modalAddCarga').modal('hide');
                    window.location.reload();
                } else {
                    alert(resp.message || 'Erro ao adicionar à carga.');
                }
            }, 'json').fail(function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erro no servidor.';
                alert(msg);
            });
        });
    </script>
@endsection
