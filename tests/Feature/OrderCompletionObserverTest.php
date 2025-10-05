<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Order;
use App\Product;
use App\Order_product;
use App\Client;

class OrderCompletionObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function fecha_quando_zera_e_reabre_quando_volta_ter_saldo()
    {
        // --- Categoria mínima (se existir tabela), para satisfazer NOT NULL de clients ---
        if (Schema::hasTable('clients_categories')) {
            if (!DB::table('clients_categories')->where('id', 1)->exists()) {
                DB::table('clients_categories')->insert([
                    'id'   => 1,
                    'name' => 'Teste',
                ]);
            }
        } elseif (Schema::hasTable('categories')) {
            if (!DB::table('categories')->where('id', 1)->exists()) {
                DB::table('categories')->insert([
                    'id'   => 1,
                    'name' => 'Teste',
                ]);
            }
        }

        // --- Cliente obrigatório p/ orders.client_id ---
        $clientAttrs = ['name' => 'Cliente Teste'];

        // atende possíveis esquemas (id_categoria OU category_id)
        if (Schema::hasColumn('clients', 'id_categoria')) {
            $clientAttrs['id_categoria'] = 1;
        } elseif (Schema::hasColumn('clients', 'category_id')) {
            $clientAttrs['category_id'] = 1;
        }

        $client = Client::create($clientAttrs);

        // --- Pedido com 1 produto (usar order_number como STRING) ---
        $order = Order::create([
            'client_id'      => $client->id,
            'order_number'   => '2001', // importa: order_products.order_id deve ser igual a este valor (string)
            'order_date'     => '2025-10-04 10:00:00',
            'withdraw'       => 'entregar', // CIF
            'complete_order' => 0,
        ]);

        // --- Produto mínimo ---
        $p = Product::create([
            'name'                      => 'P',
            'current_stock'             => 0,
            'daily_production_forecast' => 0,
        ]);

        // +1000 -> pedido deve permanecer ABERTO (complete_order = 0)
        Order_product::create([
            'order_id'      => '2001',              // MESMO valor do order_number
            'product_id'    => $p->id,
            'quant'         => 1000,
            'delivery_date' => '2025-10-04 11:00:00',
        ]);
        $order->refresh();
        $this->assertSame(0, (int) $order->complete_order, 'Deveria continuar aberto com saldo > 0');

        // -1000 -> zera -> pedido deve FECHAR (complete_order = 1)
        Order_product::create([
            'order_id'      => '2001',
            'product_id'    => $p->id,
            'quant'         => -1000,
            'delivery_date' => '2025-10-04 12:00:00',
        ]);
        $order->refresh();
        $this->assertSame(1, (int) $order->complete_order, 'Deveria fechar ao zerar');

        // +50 -> volta a ter saldo -> pedido deve REABRIR (complete_order = 0)
        Order_product::create([
            'order_id'      => '2001',
            'product_id'    => $p->id,
            'quant'         => 50,
            'delivery_date' => '2025-10-04 13:00:00',
        ]);
        $order->refresh();
        $this->assertSame(0, (int) $order->complete_order, 'Deveria reabrir ao voltar ter saldo');
    }
}
