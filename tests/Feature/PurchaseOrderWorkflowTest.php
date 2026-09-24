<?php

namespace Tests\Feature;

use App\Permission_link;
use App\PurchaseOrder;
use App\PurchaseQuote;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Valida o fluxo, as permissões e os arquivos do módulo de compras.
class PurchaseOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Administrador',
            'email' => 'compras-admin@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 1,
        ]);
    }

    public function test_creates_a_draft_with_items(): void
    {
        $response = $this->actingAs($this->admin)->post(route('purchases.store'), [
            'requester_id' => $this->admin->id,
            'request_date' => now()->toDateString(),
            'description' => 'Compra de materiais para manutenção.',
            'notes' => 'Prioridade normal.',
            'items' => [
                ['description' => 'Cimento CP-II', 'quantity' => 20],
                ['description' => 'Areia média', 'quantity' => 5.5],
            ],
        ]);

        $purchase = PurchaseOrder::first();

        $response->assertRedirect(route('purchases.show', $purchase));
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $purchase->status);
        $this->assertDatabaseCount('purchase_order_items', 2);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchase->id,
            'description' => 'Cimento CP-II',
        ]);
    }

    public function test_list_and_detail_pages_render(): void
    {
        $purchase = $this->createPurchase();

        $this->actingAs($this->admin)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee($purchase->number);

        $this->actingAs($this->admin)
            ->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee('Itens solicitados')
            ->assertSee('Orçamentos');

        $this->actingAs($this->admin)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Cadastrar Pedido de Compra');

        $this->actingAs($this->admin)
            ->get(route('purchases.edit', $purchase))
            ->assertOk()
            ->assertSee('Editar Pedido de Compra');
    }

    public function test_runs_the_complete_approval_workflow(): void
    {
        $purchase = $this->createPurchase();

        $this->actingAs($this->admin)
            ->post(route('purchases.start_quotes', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $this->actingAs($this->admin)
            ->post(route('purchase_quotes.store', $purchase), [
                'supplier_name' => 'Fornecedor Teste',
                'amount' => 1250.90,
                'quote_date' => now()->toDateString(),
                'notes' => 'Entrega em cinco dias.',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $quote = $purchase->quotes()->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('purchases.submit_approval', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_APPROVED,
                'decision_note' => 'Compra autorizada.',
                'approved_quote_id' => $quote->id,
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->actingAs($this->admin)
            ->post(route('purchases.finalize', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $this->assertSame(PurchaseOrder::STATUS_FINISHED, $purchase->status);
        $this->assertSame($this->admin->id, $purchase->decided_by);
        $this->assertSame($quote->id, $purchase->approved_quote_id);
        $this->assertSame($this->admin->id, $purchase->finalized_by);
        $this->assertDatabaseCount('purchase_quotes', 1);
    }

    public function test_requires_a_quote_when_approving(): void
    {
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);
        $this->createQuote($purchase);

        $response = $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_APPROVED,
            ]);

        $response->assertSessionHasErrors('approved_quote_id');
        $this->assertSame(PurchaseOrder::STATUS_AWAITING_APPROVAL, $purchase->fresh()->status);
        $this->assertNull($purchase->fresh()->approved_quote_id);
    }

    public function test_cannot_approve_a_quote_from_another_purchase(): void
    {
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);
        $otherPurchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);
        $otherQuote = $this->createQuote($otherPurchase);

        $response = $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_APPROVED,
                'approved_quote_id' => $otherQuote->id,
            ]);

        $response->assertSessionHasErrors('approved_quote_id');
        $this->assertSame(PurchaseOrder::STATUS_AWAITING_APPROVAL, $purchase->fresh()->status);
    }

    public function test_rejection_does_not_require_a_quote(): void
    {
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);

        $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_REJECTED,
                'decision_note' => 'O valor está acima do orçamento disponível.',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $this->assertSame(PurchaseOrder::STATUS_REJECTED, $purchase->status);
        $this->assertNull($purchase->approved_quote_id);
    }

    public function test_stores_and_opens_a_private_quote_attachment(): void
    {
        Storage::fake('local');
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_QUOTES);

        $this->actingAs($this->admin)
            ->post(route('purchase_quotes.store', $purchase), [
                'supplier_name' => 'Fornecedor com arquivo',
                'amount' => 1800,
                'quote_date' => now()->toDateString(),
                'attachment' => UploadedFile::fake()->create('orcamento.pdf', 250, 'application/pdf'),
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $quote = $purchase->quotes()->firstOrFail();
        $this->assertSame('orcamento.pdf', $quote->attachment_original_name);
        $this->assertSame('application/pdf', $quote->attachment_mime);
        Storage::disk('local')->assertExists($quote->attachment_path);

        $this->actingAs($this->admin)
            ->get(route('purchase_quotes.attachment', [$purchase, $quote]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_approver_can_open_a_quote_attachment(): void
    {
        Storage::fake('local');
        $approver = User::create([
            'name' => 'Aprovador de Compras',
            'email' => 'aprovador-anexo@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 2,
        ]);

        foreach (['menu-compras', 'purchases.approve'] as $permission) {
            Permission_link::create([
                'id_user' => $approver->id,
                'slug_permission_item' => $permission,
            ]);
        }

        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);
        $quote = $this->createQuote($purchase);
        $path = "purchase-quotes/{$purchase->id}/{$quote->id}/orcamento.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 arquivo de teste');
        $quote->update([
            'attachment_path' => $path,
            'attachment_original_name' => 'orcamento-aprovacao.pdf',
            'attachment_mime' => 'application/pdf',
            'attachment_size' => 24,
        ]);

        $this->actingAs($approver)
            ->get(route('purchase_quotes.attachment', [$purchase, $quote]))
            ->assertOk();
    }

    public function test_rejects_an_unsafe_quote_attachment(): void
    {
        Storage::fake('local');
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_QUOTES);

        $response = $this->actingAs($this->admin)
            ->post(route('purchase_quotes.store', $purchase), [
                'supplier_name' => 'Fornecedor inválido',
                'amount' => 900,
                'quote_date' => now()->toDateString(),
                'attachment' => UploadedFile::fake()->create('programa.exe', 20, 'application/x-msdownload'),
            ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('purchase_quotes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('purchase-quotes'));
    }

    public function test_replaces_and_deletes_the_quote_attachment(): void
    {
        Storage::fake('local');
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_QUOTES);

        $this->actingAs($this->admin)->post(route('purchase_quotes.store', $purchase), [
            'supplier_name' => 'Fornecedor Teste',
            'amount' => 1200,
            'quote_date' => now()->toDateString(),
            'attachment' => UploadedFile::fake()->create('antigo.pdf', 100, 'application/pdf'),
        ]);

        $quote = $purchase->quotes()->firstOrFail();
        $oldPath = $quote->attachment_path;

        $this->actingAs($this->admin)->put(route('purchase_quotes.update', [$purchase, $quote]), [
            'supplier_name' => 'Fornecedor Teste',
            'amount' => 1200,
            'quote_date' => now()->toDateString(),
            'attachment' => UploadedFile::fake()->image('novo.jpg'),
        ])->assertRedirect(route('purchases.show', $purchase));

        $quote->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($quote->attachment_path);
        $newPath = $quote->attachment_path;

        $this->actingAs($this->admin)
            ->delete(route('purchase_quotes.destroy', [$purchase, $quote]))
            ->assertRedirect(route('purchases.show', $purchase));

        Storage::disk('local')->assertMissing($newPath);
        $this->assertDatabaseMissing('purchase_quotes', ['id' => $quote->id]);
    }

    public function test_requires_a_reason_when_rejecting(): void
    {
        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);

        $response = $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_REJECTED,
                'decision_note' => '',
            ]);

        $response->assertSessionHasErrors('decision_note');
        $this->assertSame(PurchaseOrder::STATUS_AWAITING_APPROVAL, $purchase->fresh()->status);
    }

    public function test_user_without_approval_permission_cannot_decide(): void
    {
        $user = User::create([
            'name' => 'Usuário de Compras',
            'email' => 'compras-user@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 2,
        ]);

        Permission_link::create([
            'id_user' => $user->id,
            'slug_permission_item' => 'menu-compras',
        ]);
        Permission_link::create([
            'id_user' => $user->id,
            'slug_permission_item' => 'purchases.view',
        ]);

        $purchase = $this->createPurchase(PurchaseOrder::STATUS_AWAITING_APPROVAL);

        $response = $this->actingAs($user)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_APPROVED,
            ]);

        $response->assertRedirect(route('purchases.show', $purchase));
        $response->assertSessionHasErrors('no-access');
        $this->assertSame(PurchaseOrder::STATUS_AWAITING_APPROVAL, $purchase->fresh()->status);
    }

    private function createPurchase(string $status = PurchaseOrder::STATUS_DRAFT): PurchaseOrder
    {
        $purchase = PurchaseOrder::create([
            'requester_id' => $this->admin->id,
            'created_by' => $this->admin->id,
            'request_date' => now()->toDateString(),
            'description' => 'Pedido usado no teste do fluxo de compras.',
            'status' => $status,
        ]);

        $purchase->items()->create([
            'description' => 'Material de teste',
            'quantity' => 10,
        ]);

        return $purchase;
    }

    private function createQuote(PurchaseOrder $purchase): PurchaseQuote
    {
        return $purchase->quotes()->create([
            'supplier_name' => 'Fornecedor para aprovação',
            'amount' => 1500,
            'quote_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);
    }
}
