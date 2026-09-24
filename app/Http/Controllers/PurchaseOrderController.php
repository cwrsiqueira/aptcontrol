<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\PurchaseOrder;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-compras');
    }

    public function index(Request $request)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.view')) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        if (!array_key_exists($status, PurchaseOrder::statusOptions())) {
            $status = '';
        }

        $purchases = PurchaseOrder::with(['requester', 'creator'])
            ->withMin('quotes', 'amount')
            ->when($q !== '', function ($query) use ($q) {
                $number = (int) preg_replace('/\D+/', '', $q);
                $query->where(function ($filter) use ($q, $number) {
                    $filter->where('description', 'like', "%{$q}%")
                        ->orWhereHas('requester', function ($requester) use ($q) {
                            $requester->where('name', 'like', "%{$q}%");
                        });

                    if ($number > 0) {
                        $filter->orWhere('id', $number);
                    }
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('purchases.index', [
            'user' => Auth::user(),
            'user_permissions' => $userPermissions,
            'purchases' => $purchases,
            'statusOptions' => PurchaseOrder::statusOptions(),
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create()
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.create')) {
            return redirect()->route('purchases.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('purchases.create', [
            'user' => Auth::user(),
            'user_permissions' => $userPermissions,
            'purchase' => new PurchaseOrder(),
            'requesters' => $this->requesters(),
        ]);
    }

    public function store(Request $request)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.create')) {
            return redirect()->route('purchases.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $data = $this->validatePurchase($request);

        $purchase = DB::transaction(function () use ($data) {
            $purchase = PurchaseOrder::create([
                'requester_id' => $data['requester_id'],
                'created_by' => Auth::id(),
                'request_date' => $data['request_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
            ]);

            $purchase->items()->createMany($this->itemsFrom($data));

            return $purchase;
        });

        Helper::saveLog(Auth::id(), 'Cadastro', $purchase->id, $purchase->number, 'Compras');

        return redirect()->route('purchases.show', $purchase)->with('success', 'Pedido de compra cadastrado com sucesso!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.view')) {
            return redirect()->route('purchases.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $purchaseOrder->load([
            'requester',
            'creator',
            'decisionUser',
            'finalizedUser',
            'items',
            'quotes.creator',
        ]);

        return view('purchases.show', [
            'user' => Auth::user(),
            'user_permissions' => $userPermissions,
            'purchase' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.update')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'O pedido só pode ser editado enquanto estiver em rascunho.']);
        }

        $purchaseOrder->load('items');

        return view('purchases.edit', [
            'user' => Auth::user(),
            'user_permissions' => $userPermissions,
            'purchase' => $purchaseOrder,
            'requesters' => $this->requesters(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.update')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'O pedido só pode ser editado enquanto estiver em rascunho.']);
        }

        $data = $this->validatePurchase($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'requester_id' => $data['requester_id'],
                'request_date' => $data['request_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
            ]);

            // O pedido ainda está em rascunho, então os itens podem ser substituídos com segurança.
            $purchaseOrder->items()->delete();
            $purchaseOrder->items()->createMany($this->itemsFrom($data));
        });

        Helper::saveLog(Auth::id(), 'Alteração', $purchaseOrder->id, $purchaseOrder->number, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Pedido de compra atualizado com sucesso!');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.delete')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if (!$purchaseOrder->canEdit()) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'Somente pedidos em rascunho podem ser excluídos.']);
        }

        $id = $purchaseOrder->id;
        $number = $purchaseOrder->number;
        $purchaseOrder->delete();

        Helper::saveLog(Auth::id(), 'Deleção', $id, $number, 'Compras');

        return redirect()->route('purchases.index')->with('success', 'Pedido de compra excluído com sucesso!');
    }

    public function startQuotes(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.update')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'O pedido não está em rascunho.']);
        }

        if (!$purchaseOrder->items()->exists()) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['items' => 'Cadastre pelo menos um item antes de iniciar os orçamentos.']);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_AWAITING_QUOTES]);
        Helper::saveLog(Auth::id(), 'Orçamentos', $purchaseOrder->id, $purchaseOrder->number, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Pedido enviado para a etapa de orçamentos.');
    }

    public function submitApproval(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.update')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_AWAITING_QUOTES) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'O pedido não está aguardando orçamentos.']);
        }

        if (!$purchaseOrder->quotes()->exists()) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['quotes' => 'Cadastre pelo menos um orçamento antes de solicitar aprovação.']);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_AWAITING_APPROVAL]);
        Helper::saveLog(Auth::id(), 'Envio para aprovação', $purchaseOrder->id, $purchaseOrder->number, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Pedido enviado para aprovação.');
    }

    public function decide(Request $request, PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.approve')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_AWAITING_APPROVAL) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'O pedido não está aguardando aprovação.']);
        }

        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'decision_note' => 'nullable|required_if:decision,rejected|string|max:2000',
        ], [
            'decision_note.required_if' => 'Informe a justificativa da reprovação.',
        ], [
            'decision' => 'Decisão',
            'decision_note' => 'Observação da decisão',
        ]);

        $purchaseOrder->update([
            'status' => $data['decision'],
            'decided_by' => Auth::id(),
            'decided_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ]);

        $action = $data['decision'] === PurchaseOrder::STATUS_APPROVED ? 'Aprovação' : 'Reprovação';
        Helper::saveLog(Auth::id(), $action, $purchaseOrder->id, $purchaseOrder->number, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)
            ->with('success', $data['decision'] === PurchaseOrder::STATUS_APPROVED
                ? 'Pedido aprovado com sucesso!'
                : 'Pedido reprovado.');
    }

    public function finalize(PurchaseOrder $purchaseOrder)
    {
        $userPermissions = Helper::get_permissions();
        if (!$this->hasPermission($userPermissions, 'purchases.finalize')) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_APPROVED) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'Somente pedidos aprovados podem ser finalizados.']);
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_FINISHED,
            'finalized_by' => Auth::id(),
            'finalized_at' => now(),
        ]);

        Helper::saveLog(Auth::id(), 'Finalização', $purchaseOrder->id, $purchaseOrder->number, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Pedido de compra finalizado.');
    }

    private function validatePurchase(Request $request): array
    {
        return $request->validate([
            'requester_id' => 'required|exists:users,id',
            'request_date' => 'required|date',
            'description' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|gt:0',
        ], [], [
            'requester_id' => 'Solicitante',
            'request_date' => 'Data da solicitação',
            'description' => 'Descrição ou justificativa',
            'notes' => 'Observações',
            'items' => 'Itens',
            'items.*.description' => 'Descrição do item',
            'items.*.quantity' => 'Quantidade do item',
        ]);
    }

    private function itemsFrom(array $data): array
    {
        return collect($data['items'])->map(function ($item) {
            return [
                'description' => trim($item['description']),
                'quantity' => $item['quantity'],
            ];
        })->values()->all();
    }

    private function requesters()
    {
        return User::where('confirmed_user', '<>', 0)->orderBy('name')->get();
    }

    private function hasPermission(array $permissions, string $slug): bool
    {
        return Auth::user()->is_admin || in_array($slug, $permissions, true);
    }
}
