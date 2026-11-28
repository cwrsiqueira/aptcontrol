<?php

namespace Tests\Feature;

use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_confirmed_user_cannot_be_mass_assigned_on_create(): void
    {
        $user = User::create([
            'name' => 'Tentativa Admin',
            'email' => 'mass-assign@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 1,
        ]);

        $this->assertSame(0, (int) $user->fresh()->confirmed_user);
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_user_without_delete_permission_cannot_destroy_product(): void
    {
        $product = Product::create([
            'name' => 'Produto Protegido',
            'current_stock' => 0,
            'daily_production_forecast' => 1,
        ]);

        $user = $this->makeUser([
            'name' => 'Sem exclusão',
            'email' => 'sem-delete@example.com',
            'confirmed_user' => 2,
        ]);

        $response = $this->actingAs($user)->delete(route('products.destroy', $product));

        // Gate/middleware pode responder 403; o controller também redireciona com erro.
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect(),
            'Esperado 403 ou redirect, recebeu ' . $response->status()
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Protegido',
            'deleted_at' => null,
        ]);
        $this->assertNull(Product::withTrashed()->find($product->id)?->deleted_at);
    }

    public function test_soft_deleted_product_is_not_openable_on_cc_product(): void
    {
        $admin = $this->makeUser([
            'name' => 'Admin CC',
            'email' => 'admin-cc@example.com',
            'confirmed_user' => 1,
        ]);

        $product = Product::create([
            'name' => 'Produto Removido',
            'current_stock' => 0,
            'daily_production_forecast' => 1,
        ]);

        $product->delete();

        $this->actingAs($admin)
            ->get(route('cc_product', ['id' => $product->id]))
            ->assertNotFound();
    }
}
