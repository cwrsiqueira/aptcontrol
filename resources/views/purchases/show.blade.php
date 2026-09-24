{{-- Exibe o pedido, os orçamentos e as ações de cada etapa. --}}
@extends('layouts.template')

@section('title', 'Pedido de Compra ' . $purchase->number)

@section('content')
    @php
        $canUpdate = in_array('purchases.update', $user_permissions) || Auth::user()->is_admin;
        $canDelete = in_array('purchases.delete', $user_permissions) || Auth::user()->is_admin;
        $canQuotes = in_array('purchases.quotes', $user_permissions) || Auth::user()->is_admin;
        $canApprove = in_array('purchases.approve', $user_permissions) || Auth::user()->is_admin;
        $canFinalize = in_array('purchases.finalize', $user_permissions) || Auth::user()->is_admin;

        $quotesDone = in_array($purchase->status, ['awaiting_approval', 'approved', 'rejected', 'finished']);
        $approvalDone = in_array($purchase->status, ['approved', 'rejected', 'finished']);
        $finalizationDone = $purchase->status === 'finished';
    @endphp

    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-1">Pedido de Compra {{ $purchase->number }}</h2>
                <span class="badge badge-{{ $purchase->status_badge }}">{{ $purchase->status_label }}</span>
            </div>
            <a class="btn btn-sm btn-light" href="{{ route('purchases.index') }}">< Compras</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-ban"></i> Erro!
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
                <i class="icon fas fa-check"></i> {{ session('success') }}
            </div>
        @endif

        <div class="row mb-3 purchase-progress">
            <div class="col-md-3 mb-2">
                <div class="card h-100 border-success">
                    <div class="card-body py-3">
                        <small class="text-muted">1. Solicitação</small>
                        <div class="font-weight-bold text-success">Concluída</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card h-100 {{ $purchase->status === 'awaiting_quotes' ? 'border-info' : ($quotesDone ? 'border-success' : '') }}">
                    <div class="card-body py-3">
                        <small class="text-muted">2. Orçamentos</small>
                        <div class="font-weight-bold {{ $purchase->status === 'awaiting_quotes' ? 'text-info' : ($quotesDone ? 'text-success' : 'text-muted') }}">
                            {{ $quotesDone ? 'Concluídos' : ($purchase->status === 'awaiting_quotes' ? 'Em andamento' : 'Pendente') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card h-100 {{ $purchase->status === 'awaiting_approval' ? 'border-warning' : ($approvalDone ? ($purchase->status === 'rejected' ? 'border-danger' : 'border-success') : '') }}">
                    <div class="card-body py-3">
                        <small class="text-muted">3. Aprovação</small>
                        <div class="font-weight-bold {{ $purchase->status === 'rejected' ? 'text-danger' : ($purchase->status === 'awaiting_approval' ? 'text-warning' : ($approvalDone ? 'text-success' : 'text-muted')) }}">
                            @if ($purchase->status === 'rejected')
                                Reprovado
                            @elseif ($approvalDone)
                                Aprovado
                            @elseif ($purchase->status === 'awaiting_approval')
                                Em análise
                            @else
                                Pendente
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card h-100 {{ $purchase->status === 'approved' ? 'border-info' : ($finalizationDone ? 'border-success' : '') }}">
                    <div class="card-body py-3">
                        <small class="text-muted">4. Finalização</small>
                        <div class="font-weight-bold {{ $purchase->status === 'approved' ? 'text-info' : ($finalizationDone ? 'text-success' : 'text-muted') }}">
                            {{ $finalizationDone
                                ? 'Concluída'
                                : ($purchase->status === 'approved'
                                    ? 'Disponível'
                                    : ($purchase->status === 'rejected' ? 'Não aplicável' : 'Pendente')) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Solicitação</strong>
                <div>
                    @if ($purchase->canEdit() && $canUpdate)
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('purchases.edit', $purchase) }}">Editar</a>
                    @endif
                    @if ($purchase->canEdit() && $canDelete)
                        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Excluir este pedido de compra?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Solicitante</dt>
                    <dd class="col-sm-9">{{ optional($purchase->requester)->name ?? 'Usuário removido' }}</dd>

                    <dt class="col-sm-3">Data</dt>
                    <dd class="col-sm-9">{{ $purchase->request_date->format('d/m/Y') }}</dd>

                    <dt class="col-sm-3">Criado por</dt>
                    <dd class="col-sm-9">{{ optional($purchase->creator)->name ?? 'Usuário removido' }}</dd>

                    <dt class="col-sm-3">Criado em</dt>
                    <dd class="col-sm-9">{{ optional($purchase->created_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Atualizado em</dt>
                    <dd class="col-sm-9">{{ optional($purchase->updated_at)->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-3">Descrição/justificativa</dt>
                    <dd class="col-sm-9">{{ $purchase->description }}</dd>

                    <dt class="col-sm-3">Observações</dt>
                    <dd class="col-sm-9">{{ $purchase->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Itens solicitados</strong></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Descrição</th>
                            <th class="text-right">Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-right">{{ number_format((float) $item->quantity, 3, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($purchase->status === 'draft' && $canUpdate)
            <div class="card mb-3 border-info">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <strong>Próxima etapa: orçamentos</strong>
                        <div class="text-muted small">Ao iniciar, os dados e itens da solicitação não poderão mais ser alterados.</div>
                    </div>
                    <form action="{{ route('purchases.start_quotes', $purchase) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info">Iniciar orçamentos</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Orçamentos</strong>
                <span class="badge badge-light">{{ $purchase->quotes->count() }} cadastrado(s)</span>
            </div>

            @if ($purchase->status === 'awaiting_quotes' && $canQuotes)
                <div class="card-body border-bottom">
                    <form action="{{ route('purchase_quotes.store', $purchase) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="supplier_name">Fornecedor *</label>
                                <input type="text" class="form-control" id="supplier_name" name="supplier_name"
                                    maxlength="150" required value="{{ old('supplier_name') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="amount">Valor *</label>
                                <input type="number" class="form-control" id="amount" name="amount"
                                    min="0.01" step="0.01" required value="{{ old('amount') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="quote_date">Data *</label>
                                <input type="date" class="form-control" id="quote_date" name="quote_date"
                                    required value="{{ old('quote_date', now()->toDateString()) }}">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">Adicionar</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="quote_notes">Observações</label>
                            <textarea class="form-control" id="quote_notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label for="quote_attachment">Arquivo do orçamento</label>
                            <input type="file" class="form-control-file @error('attachment') is-invalid @enderror"
                                id="quote_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                            @error('attachment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">PDF, JPG ou PNG, com no máximo 10 MB.</small>
                        </div>
                    </form>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Fornecedor</th>
                            <th>Data</th>
                            <th class="text-right">Valor</th>
                            <th>Observações</th>
                            <th>Arquivo</th>
                            @if ($purchase->status === 'awaiting_quotes' && $canQuotes)
                                <th class="text-right">Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchase->quotes as $quote)
                            <tr>
                                <td>{{ $quote->supplier_name }}</td>
                                <td>{{ $quote->quote_date->format('d/m/Y') }}</td>
                                <td class="text-right">R$ {{ number_format($quote->amount, 2, ',', '.') }}</td>
                                <td>{{ $quote->notes ?: '—' }}</td>
                                <td>
                                    @if ($quote->attachment_path)
                                        <a href="{{ route('purchase_quotes.attachment', [$purchase, $quote]) }}"
                                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"
                                            title="{{ $quote->attachment_original_name }}">
                                            <i class="fas fa-paperclip"></i> Abrir
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @if ($purchase->status === 'awaiting_quotes' && $canQuotes)
                                    <td class="text-right text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                            data-target="#edit-quote-{{ $quote->id }}">Editar</button>
                                        <form action="{{ route('purchase_quotes.destroy', [$purchase, $quote]) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Excluir este orçamento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $purchase->status === 'awaiting_quotes' && $canQuotes ? 6 : 5 }}"
                                    class="text-center text-muted py-4">Nenhum orçamento cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($purchase->status === 'awaiting_quotes' && $canUpdate)
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">É necessário pelo menos um orçamento para continuar.</small>
                    <form action="{{ route('purchases.submit_approval', $purchase) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning" @if ($purchase->quotes->isEmpty()) disabled @endif>
                            Enviar para aprovação
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if ($purchase->status === 'awaiting_approval' && $canApprove)
            <div class="card mb-3 border-warning">
                <div class="card-header"><strong>Decisão da aprovação</strong></div>
                <div class="card-body">
                    <form action="{{ route('purchases.decision', $purchase) }}" method="POST">
                        @csrf
                        {{-- A aprovação registra qual orçamento foi escolhido. --}}
                        <div class="form-group">
                            <label for="approved_quote_id">Orçamento aprovado *</label>
                            <select class="form-control @error('approved_quote_id') is-invalid @enderror"
                                id="approved_quote_id" name="approved_quote_id" required>
                                <option value="">Selecione um orçamento</option>
                                @foreach ($purchase->quotes as $quote)
                                    <option value="{{ $quote->id }}" @if ((string) old('approved_quote_id') === (string) $quote->id) selected @endif>
                                        {{ $quote->supplier_name }} — R$ {{ number_format($quote->amount, 2, ',', '.') }} — {{ $quote->quote_date->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('approved_quote_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Obrigatório somente para aprovar o pedido.</small>
                        </div>
                        <div class="form-group">
                            <label for="decision_note">Observação</label>
                            <textarea class="form-control" id="decision_note" name="decision_note" rows="3" maxlength="2000"
                                placeholder="Obrigatória em caso de reprovação">{{ old('decision_note') }}</textarea>
                        </div>
                        <button type="submit" name="decision" value="approved" class="btn btn-success">Aprovar</button>
                        <button type="submit" name="decision" value="rejected" class="btn btn-danger" formnovalidate>Reprovar</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($purchase->decided_at)
            <div class="card mb-3 {{ $purchase->status === 'rejected' ? 'border-danger' : 'border-success' }}">
                <div class="card-header"><strong>Decisão registrada</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Decisão</dt>
                        <dd class="col-sm-9"><span class="badge badge-{{ $purchase->status === 'rejected' ? 'danger' : 'success' }}">{{ $purchase->status === 'rejected' ? 'Reprovado' : 'Aprovado' }}</span></dd>
                        <dt class="col-sm-3">Responsável</dt>
                        <dd class="col-sm-9">{{ optional($purchase->decisionUser)->name ?? 'Usuário removido' }}</dd>
                        <dt class="col-sm-3">Data e horário</dt>
                        <dd class="col-sm-9">{{ $purchase->decided_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-3">Observação</dt>
                        <dd class="col-sm-9">{{ $purchase->decision_note ?: '—' }}</dd>
                        @if ($purchase->approvedQuote)
                            <dt class="col-sm-3">Orçamento aprovado</dt>
                            <dd class="col-sm-9">
                                {{ $purchase->approvedQuote->supplier_name }} —
                                R$ {{ number_format($purchase->approvedQuote->amount, 2, ',', '.') }} —
                                {{ $purchase->approvedQuote->quote_date->format('d/m/Y') }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        @endif

        @if ($purchase->status === 'approved' && $canFinalize)
            <div class="card mb-3 border-info">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Pedido aprovado</strong>
                        <div class="small text-muted">Finalize quando o processo de compra estiver concluído.</div>
                    </div>
                    <form action="{{ route('purchases.finalize', $purchase) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Finalizar pedido</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($purchase->finalized_at)
            <div class="alert alert-primary">
                Finalizado por {{ optional($purchase->finalizedUser)->name ?? 'Usuário removido' }}
                em {{ $purchase->finalized_at->format('d/m/Y H:i') }}.
            </div>
        @endif

        @if ($purchase->status === 'awaiting_quotes' && $canQuotes)
            @foreach ($purchase->quotes as $quote)
                <div class="modal fade" id="edit-quote-{{ $quote->id }}" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form action="{{ route('purchase_quotes.update', [$purchase, $quote]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar orçamento</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Fornecedor *</label>
                                        <input type="text" class="form-control" name="supplier_name" maxlength="150"
                                            required value="{{ $quote->supplier_name }}">
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Valor *</label>
                                            <input type="number" class="form-control" name="amount" min="0.01" step="0.01"
                                                required value="{{ $quote->amount }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Data *</label>
                                            <input type="date" class="form-control" name="quote_date" required
                                                value="{{ $quote->quote_date->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Observações</label>
                                        <textarea class="form-control" name="notes" rows="3" maxlength="2000">{{ $quote->notes }}</textarea>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label>Arquivo do orçamento</label>
                                        @if ($quote->attachment_path)
                                            <div class="mb-2">
                                                <a href="{{ route('purchase_quotes.attachment', [$purchase, $quote]) }}"
                                                    target="_blank" rel="noopener">
                                                    <i class="fas fa-paperclip"></i> {{ $quote->attachment_original_name }}
                                                </a>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control-file" name="attachment"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                        <small class="form-text text-muted">PDF, JPG ou PNG, com no máximo 10 MB. Um novo arquivo substitui o atual.</small>
                                        @if ($quote->attachment_path)
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remove_attachment"
                                                    value="1" id="remove-attachment-{{ $quote->id }}">
                                                <label class="form-check-label" for="remove-attachment-{{ $quote->id }}">Remover arquivo atual</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </main>
@endsection

@section('css')
    <style>
        .purchase-progress .card {
            box-shadow: none;
        }
    </style>
@endsection
