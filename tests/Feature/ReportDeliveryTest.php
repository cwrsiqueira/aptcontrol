<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Seus models App\*
use App\User;
use App\Client;
use App\Helpers\Helper;
use App\Seller;
use App\Order;
use App\Product;
use App\Order_product;

class ReportDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um usuário "admin" (no seu projeto admin = confirmed_user === 1)
     * e autentica para passar pelos middlewares/verificações.
     */
    protected function actingAsAdmin(): void
    {
        $u = new User();
        $u->name = 'Admin';
        $u->email = 'admin@example.com';
        $u->password = bcrypt('secret');
        $u->confirmed_user = 1; // <-- ESSENCIAL: seu accessor is_admin usa confirmed_user
        $u->save();

        $this->actingAs($u);
    }

    /**
     * Semeia um cenário básico:
     * - 1 categoria (se existir a tabela), 1 cliente, 1 vendedor, 1 produto,
     * - 1 pedido aberto (complete_order=0) com 3 lançamentos:
     *   +1000 (03/10), -200 (04/10), +100 (05/10) => saldo final esperado: 900.
     * Retorna [ $product, $order ].
     */
    protected function seedBasicScenario(): array
    {
        // Categoria opcional (apenas se a tabela existir)
        if (Schema::hasTable('clients_categories')) {
            // evita conflito de PK duplicada em reruns
            if (!DB::table('clients_categories')->where('id', 1)->exists()) {
                DB::table('clients_categories')->insert([
                    'id'   => 1,
                    'name' => 'Categoria A',
                ]);
            }
        }

        $client = new Client();
        $client->name = 'Cliente A';
        $client->contact = 'João';
        // se sua coluna aceita null, pode deixar sem category_id;
        // se não aceita, use 1 (e criamos a categoria acima se existir a tabela)
        if (Schema::hasColumn($client->getTable(), 'id_categoria')) {
            $client->id_categoria = 1;
        }
        $client->save();

        $seller = new Seller();
        $seller->name = 'Vendedor X';
        $seller->contact_type = 'outro';
        $seller->save();

        $product = new Product();
        $product->name = 'Produto P';
        $product->current_stock = 0;
        $product->daily_production_forecast = 10;
        $product->save();

        // Pedido ABERTO
        $order = new Order();
        $order->order_number = 1001; // importante: suas joins usam order_number
        $order->order_date   = '2025-10-04';
        $order->client_id    = $client->id;
        $order->seller_id    = $seller->id;
        $order->withdraw     = 'entregar'; // CIF
        $order->complete_order = 0;
        $order->order_total   = 0;
        $order->favorite_date   = 0;
        $order->save();

        // Lançamentos:
        $op1 = new Order_product();
        $op1->order_id = 1001; // join pelo order_number
        $op1->product_id = $product->id;
        $op1->quant = 1000;
        $op1->delivery_date = '2025-10-03';
        $op1->save();

        $op2 = new Order_product();
        $op2->order_id = 1001;
        $op2->product_id = $product->id;
        $op2->quant = -200;
        $op2->delivery_date = '2025-10-04';
        $op2->save();

        $op3 = new Order_product();
        $op3->order_id = 1001;
        $op3->product_id = $product->id;
        $op3->quant = 100;
        $op3->delivery_date = '2025-10-05';
        $op3->save();

        return [$product, $order];
    }

    /** @test */
    public function pendentes_sem_filtros_batem_com_helper()
    {
        $this->actingAsAdmin();
        [$product, $order] = $this->seedBasicScenario();

        // pendentes (sem período)
        $resp = $this->get(route('report_delivery', [
            'products' => [$product->id],
            'status'   => 'pendentes',
        ]));

        $resp->assertStatus(200)->assertViewHas('meta');
        $meta = $resp->viewData('meta');
        $totalRelatorio = (int) ($meta['total_pendentes'] ?? -1);

        // Helper oficial
        $helper = Helper::day_delivery_calc($product->id);
        $totalHelper = (int) ($helper['quant_total'] ?? -2);

        $this->assertSame(
            $totalHelper,
            $totalRelatorio,
            "Divergência: Helper={$totalHelper} x Relatório={$totalRelatorio}"
        );
    }

    /** @test */
    public function ambos_igual_pendentes_mais_realizadas()
    {
        $this->actingAsAdmin();
        [$product, $order] = $this->seedBasicScenario();

        // pendentes
        $rPend = $this->get(route('report_delivery', [
            'products'  => [$product->id],
            'status'    => 'pendentes',
            'date_field' => 'delivery',
        ]));
        $rPend->assertStatus(200)->assertViewHas('meta');
        $pend = (int) ($rPend->viewData('meta')['total_pendentes'] ?? 0);

        // realizadas
        $rReal = $this->get(route('report_delivery', [
            'products'  => [$product->id],
            'status'    => 'realizadas',
            'date_field' => 'delivery',
        ]));
        $rReal->assertStatus(200)->assertViewHas('meta');
        $real = (int) ($rReal->viewData('meta')['total_realizadas'] ?? 0);

        // ambos
        $rAmb = $this->get(route('report_delivery', [
            'products'  => [$product->id],
            'status'    => 'ambos',
            'date_field' => 'delivery',
        ]));
        $rAmb->assertStatus(200)->assertViewHas('meta');
        $metaAmb = $rAmb->viewData('meta');
        $somaAmbos = (int) ($metaAmb['total_pendentes'] ?? 0) + (int) ($metaAmb['total_realizadas'] ?? 0);

        $this->assertSame(
            $pend + $real,
            $somaAmbos,
            "Divergência: (Pendentes {$pend}) + (Realizadas {$real}) != (Ambos {$somaAmbos})"
        );
    }

    /** @test */
    public function filtro_de_um_dia_precisa_trazer_o_dia_inteiro()
    {
        $this->actingAsAdmin();
        [$product, $order] = $this->seedBasicScenario();

        // 04/10/2025 tem a saída de 200
        $resp = $this->get(route('report_delivery', [
            'products'   => [$product->id],
            'status'     => 'realizadas',
            'date_field' => 'delivery',
            'date_ini'   => '2025-10-04',
            'date_fin'   => '2025-10-04',
        ]));

        $resp->assertStatus(200)->assertViewHas('meta');
        $meta = $resp->viewData('meta');
        $totalReal = (int) ($meta['total_realizadas'] ?? 0);

        $this->assertSame(
            200,
            $totalReal,
            "Era esperado encontrar 200 entregues em 2025-10-04, mas o relatório retornou {$totalReal}."
        );
    }
}
