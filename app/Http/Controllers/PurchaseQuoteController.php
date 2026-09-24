<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\PurchaseOrder;
use App\PurchaseQuote;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

// Gerencia orçamentos e seus arquivos privados.
class PurchaseQuoteController extends Controller
{
    private const ATTACHMENT_DISK = 'local';
    private const ATTACHMENT_DIRECTORY = 'purchase-quotes';

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
        $attachment = $data['attachment'] ?? null;
        unset($data['attachment'], $data['remove_attachment']);

        $storedPath = null;
        try {
            $quote = DB::transaction(function () use ($purchaseOrder, $data, $attachment, &$storedPath) {
                $quote = $purchaseOrder->quotes()->create($data + ['created_by' => Auth::id()]);

                if ($attachment) {
                    $attachmentData = $this->storeAttachment($purchaseOrder, $quote, $attachment);
                    $storedPath = $attachmentData['attachment_path'];
                    $quote->update($attachmentData);
                }

                return $quote;
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk(self::ATTACHMENT_DISK)->delete($storedPath);
            }

            throw $exception;
        }

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
        $data = $this->validateQuote($request);
        $attachment = $data['attachment'] ?? null;
        $removeAttachment = (bool) ($data['remove_attachment'] ?? false);
        unset($data['attachment'], $data['remove_attachment']);

        $oldPath = $quote->attachment_path;
        $storedPath = null;

        try {
            DB::transaction(function () use ($purchaseOrder, $quote, $data, $attachment, $removeAttachment, &$storedPath) {
                $quote->update($data);

                if ($attachment) {
                    $attachmentData = $this->storeAttachment($purchaseOrder, $quote, $attachment);
                    $storedPath = $attachmentData['attachment_path'];
                    $quote->update($attachmentData);
                } elseif ($removeAttachment) {
                    $quote->update($this->emptyAttachment());
                }
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk(self::ATTACHMENT_DISK)->delete($storedPath);
            }

            throw $exception;
        }

        // Remove o arquivo anterior somente após salvar a alteração.
        if (($attachment || $removeAttachment) && $oldPath && $oldPath !== $storedPath) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($oldPath);
        }

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
        $attachmentPath = $quote->attachment_path;
        $quote->delete();

        if ($attachmentPath) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($attachmentPath);
        }

        Helper::saveLog(Auth::id(), 'Exclusão de orçamento', $purchaseOrder->id, $supplier, 'Compras');

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', 'Orçamento excluído com sucesso!');
    }

    public function attachment(PurchaseOrder $purchaseOrder, PurchaseQuote $purchaseQuote)
    {
        if (!$this->canViewQuotes()) {
            return redirect()->route('purchases.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $quote = $purchaseOrder->quotes()->findOrFail($purchaseQuote->id);
        $disk = Storage::disk(self::ATTACHMENT_DISK);

        if (!$quote->attachment_path || !$disk->exists($quote->attachment_path)) {
            abort(404, 'Arquivo do orçamento não encontrado.');
        }

        $fileName = $quote->attachment_original_name ?: 'orcamento';
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', Str::ascii($fileName)) ?: 'orcamento';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $fileName,
            $fallbackName
        );

        // O arquivo privado só é entregue após a autorização.
        return response()->file($disk->path($quote->attachment_path), [
            'Content-Type' => $quote->attachment_mime ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'supplier_name' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'quote_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:10240',
            'remove_attachment' => 'nullable|boolean',
        ], [], [
            'supplier_name' => 'Fornecedor',
            'amount' => 'Valor',
            'quote_date' => 'Data do orçamento',
            'notes' => 'Observações',
            'attachment' => 'Arquivo do orçamento',
        ]);
    }

    private function storeAttachment(
        PurchaseOrder $purchaseOrder,
        PurchaseQuote $quote,
        UploadedFile $attachment
    ): array {
        $extension = strtolower((string) $attachment->extension());
        $directory = self::ATTACHMENT_DIRECTORY . "/{$purchaseOrder->id}/{$quote->id}";
        $path = $attachment->storeAs($directory, Str::uuid() . ".{$extension}", self::ATTACHMENT_DISK);

        if (!$path) {
            throw new RuntimeException('Não foi possível armazenar o arquivo do orçamento.');
        }

        return [
            'attachment_path' => $path,
            'attachment_original_name' => $this->cleanOriginalName($attachment->getClientOriginalName()),
            'attachment_mime' => $attachment->getMimeType(),
            'attachment_size' => $attachment->getSize(),
        ];
    }

    private function cleanOriginalName(string $fileName): string
    {
        $fileName = basename(str_replace('\\', '/', $fileName));
        $fileName = preg_replace('/[\x00-\x1F\x7F]/u', '', $fileName) ?: 'orcamento';

        return Str::limit($fileName, 255, '');
    }

    private function emptyAttachment(): array
    {
        return [
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ];
    }

    private function canManageQuotes(): bool
    {
        return Auth::user()->is_admin
            || in_array('purchases.quotes', Helper::get_permissions(), true);
    }

    private function canViewQuotes(): bool
    {
        if (Auth::user()->is_admin) {
            return true;
        }

        return !empty(array_intersect(Helper::get_permissions(), [
            'purchases.view',
            'purchases.quotes',
            'purchases.approve',
        ]));
    }
}
