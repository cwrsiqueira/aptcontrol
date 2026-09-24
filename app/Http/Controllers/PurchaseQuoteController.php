<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\PurchaseOrder;
use App\PurchaseQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseQuoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-compras');
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!$this->canManageQuotes()) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_AWAITING_QUOTES) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'Orçamentos só podem ser cadastrados durante a etapa de orçamentos.']);
        }

        $data = $this->validateQuote($request);
        $quote = $purchaseOrder->quotes()->create($data + ['created_by' => Auth::id()]);

        Helper::saveLog(Auth::id(), 'Orçamento', $purchaseOrder->id, $quote->supplier_name, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Orçamento cadastrado com sucesso!');
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder, PurchaseQuote $purchaseQuote)
    {
        if (!$this->canManageQuotes()) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_AWAITING_QUOTES) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'Orçamentos só podem ser alterados durante a etapa de orçamentos.']);
        }

        $quote = $purchaseOrder->quotes()->findOrFail($purchaseQuote->id);
        $quote->update($this->validateQuote($request));

        Helper::saveLog(Auth::id(), 'Alteração de orçamento', $purchaseOrder->id, $quote->supplier_name, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Orçamento atualizado com sucesso!');
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseQuote $purchaseQuote)
    {
        if (!$this->canManageQuotes()) {
            return redirect()->route('purchases.show', $purchaseOrder)->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_AWAITING_QUOTES) {
            return redirect()->route('purchases.show', $purchaseOrder)
                ->withErrors(['status' => 'Orçamentos só podem ser excluídos durante a etapa de orçamentos.']);
        }

        $quote = $purchaseOrder->quotes()->findOrFail($purchaseQuote->id);
        $supplier = $quote->supplier_name;
        $quote->delete();

        Helper::saveLog(Auth::id(), 'Exclusão de orçamento', $purchaseOrder->id, $supplier, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Orçamento excluído com sucesso!');
    }

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'supplier_name' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'quote_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'supplier_name' => 'Fornecedor',
            'amount' => 'Valor',
            'quote_date' => 'Data do orçamento',
            'notes' => 'Observações',
        ]);
    }

    private function canManageQuotes(): bool
    {
        return Auth::user()->is_admin
            || in_array('purchases.quotes', Helper::get_permissions(), true);
    }
}
