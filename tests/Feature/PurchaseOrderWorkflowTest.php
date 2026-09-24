<?php

namespace Tests\Feature;

use App\Permission_link;
use App\PurchaseOrder;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

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

        $this->actingAs($this->admin)
            ->post(route('purchases.submit_approval', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $this->actingAs($this->admin)
            ->post(route('purchases.decision', $purchase), [
                'decision' => PurchaseOrder::STATUS_APPROVED,
                'decision_note' => 'Compra autorizada.',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->actingAs($this->admin)
            ->post(route('purchases.finalize', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $this->assertSame(PurchaseOrder::STATUS_FINISHED, $purchase->status);
        $this->assertSame($this->admin->id, $purchase->decided_by);
        $this->assertSame($this->admin->id, $purchase->finalized_by);
        $this->assertDatabaseCount('purchase_quotes', 1);
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
}
