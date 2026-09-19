<?php

namespace Database\Seeders;

use App\Client;
use App\Load;
use App\LoadItem;
use App\Order;
use App\Order_product;
use App\Product;
use App\Seller;
use App\Truck;
use App\Zone;
use App\ZoneBairro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegacyProductionSeeder extends Seeder
{
    public function run()
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Este seeder contém dados fictícios e não pode ser executado em produção.');
        }

        // Simula pedidos criados antes do planejamento por entrega.
        DB::transaction(function () {
            $categoryId = $this->category();
            $seller = $this->seller();
            $clients = $this->clients($categoryId);
            $products = $this->products();
            $zone = $this->zone();
            $truck = $this->truck();

            $loadedItem = $this->orders($clients, $seller, $products);
            $this->legacyLoad($loadedItem, $zone, $truck);
        });
    }

    private function category(): int
    {
        DB::table('clients_categories')->updateOrInsert(
            ['name' => 'Cliente legado'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return (int) DB::table('clients_categories')->where('name', 'Cliente legado')->value('id');
    }

    private function seller(): Seller
    {
        return Seller::updateOrCreate(
            ['name' => 'Vendedor Legado'],
            ['contact_type' => 'telefone', 'contact_value' => '(11) 3000-9090']
        );
    }

    private function clients(int $categoryId): array
    {
        $items = [
            ['name' => 'Construtora Modelo Legado', 'contact' => '(11) 98888-1001', 'address' => 'Rua das Obras, 150 - Centro'],
            ['name' => 'Depósito Modelo Legado', 'contact' => '(11) 98888-1002', 'address' => 'Avenida Industrial, 900 - Distrito Industrial'],
        ];

        foreach ($items as $item) {
            $client = Client::firstOrNew(['name' => $item['name']]);
            $client->id_categoria = $categoryId;
            $client->contact = $item['contact'];
            $client->full_address = $item['address'];
            $client->save();
        }

        return Client::whereIn('name', array_column($items, 'name'))->get()->keyBy('name')->all();
    }

    private function products(): array
    {
        $items = [
            ['name' => 'Bloco Estrutural Legado 14x19x39', 'stock' => 2400, 'forecast' => 600],
            ['name' => 'Canaleta Legada 14x19x39', 'stock' => 900, 'forecast' => 300],
            ['name' => 'Piso Intertravado Legado 10x20', 'stock' => 1800, 'forecast' => 800],
            ['name' => 'Bloco Legado sem Composição', 'stock' => 500, 'forecast' => 200],
        ];

        foreach ($items as $item) {
            Product::updateOrCreate(
                ['name' => $item['name']],
                ['current_stock' => $item['stock'], 'daily_production_forecast' => $item['forecast']]
            );
        }

        return Product::whereIn('name', array_column($items, 'name'))->get()->keyBy('name')->all();
    }

    private function zone(): Zone
    {
        $zone = Zone::updateOrCreate(
            ['nome' => 'Zona Legada'],
            ['obs' => 'Zona usada na simulação de registros antigos']
        );

        ZoneBairro::firstOrCreate([
            'zone_id' => $zone->id,
            'bairro_nome' => 'Centro',
        ]);

        return $zone;
    }

    private function truck(): Truck
    {
        return Truck::updateOrCreate(
            ['placa' => 'LEG-2001'],
            [
                'responsavel' => 'Motorista Legado',
                'capacidade_paletes' => 10,
                'modelo' => 'Caminhão de Teste Legado',
                'obs' => 'Usado somente na simulação local',
            ]
        );
    }

    private function orders(array $clients, Seller $seller, array $products): Order_product
    {
        $items = [
            [
                'number' => 'PED-LEGADO-2001',
                'client' => 'Construtora Modelo Legado',
                'product' => 'Bloco Estrutural Legado 14x19x39',
                'quantity' => 1170,
                'unit_price' => 3.50,
                'date' => now()->addDays(2)->toDateString(),
                'withdraw' => 'entregar',
                'address' => 'Rua das Obras, 150',
                'neighborhood' => 'Centro',
                'zone' => 'Zona Legada',
                'pallets' => [390 => 3],
            ],
            [
                'number' => 'PED-LEGADO-2002',
                'client' => 'Depósito Modelo Legado',
                'product' => 'Canaleta Legada 14x19x39',
                'quantity' => 650,
                'unit_price' => 5.20,
                'date' => now()->addDays(3)->toDateString(),
                'withdraw' => 'entregar',
                'address' => 'Avenida Industrial, 900',
                'neighborhood' => 'Distrito Industrial',
                'zone' => 'Zona Legada',
                'pallets' => [390 => 1, 260 => 1],
            ],
            [
                'number' => 'PED-LEGADO-2003',
                'client' => 'Construtora Modelo Legado',
                'product' => 'Piso Intertravado Legado 10x20',
                'quantity' => 1040,
                'unit_price' => 4.40,
                'date' => now()->addDay()->toDateString(),
                'withdraw' => 'retirar',
                'address' => null,
                'neighborhood' => null,
                'zone' => null,
                'pallets' => [260 => 4],
            ],
            [
                'number' => 'PED-LEGADO-2004',
                'client' => 'Depósito Modelo Legado',
                'product' => 'Bloco Legado sem Composição',
                'quantity' => 500,
                'unit_price' => 2.90,
                'date' => now()->addDays(4)->toDateString(),
                'withdraw' => 'entregar',
                'address' => 'Avenida Industrial, 900',
                'neighborhood' => 'Distrito Industrial',
                'zone' => 'Zona Legada',
                'pallets' => null,
            ],
        ];

        $loadedItem = null;
        foreach ($items as $item) {
            $order = Order::firstOrNew(['order_number' => $item['number']]);
            $order->client_id = $clients[$item['client']]->id;
            $order->seller_id = $seller->id;
            $order->order_date = now()->subDays(7)->toDateString();
            $order->order_total = $item['quantity'] * $item['unit_price'];
            $order->payment = 'Aberto';
            $order->withdraw = $item['withdraw'];
            $order->endereco = $item['address'];
            $order->bairro = $item['neighborhood'];
            $order->zona = $item['zone'];
            $order->complete_order = 0;
            $order->save();

            $orderProduct = Order_product::where('order_id', $order->order_number)
                ->where('product_id', $products[$item['product']]->id)
                ->where('quant', '>', 0)
                ->first() ?? new Order_product();
            $orderProduct->order_id = $order->order_number;
            $orderProduct->product_id = $products[$item['product']]->id;
            $orderProduct->quant = $item['quantity'];
            $orderProduct->unit_price = $item['unit_price'];
            $orderProduct->total_price = $item['quantity'] * $item['unit_price'];
            $orderProduct->delivery_date = $item['date'];
            $orderProduct->favorite_delivery = 0;
            $orderProduct->checkmark = 2;
            $orderProduct->carga = $item['pallets'] ? json_encode($item['pallets']) : null;
            $orderProduct->save();

            if ($item['number'] === 'PED-LEGADO-2001') {
                $loadedItem = $orderProduct;
            }
        }

        return $loadedItem;
    }

    private function legacyLoad(Order_product $orderProduct, Zone $zone, Truck $truck): void
    {
        $load = Load::updateOrCreate(
            ['obs' => 'Carga simulada com item legado'],
            [
                'truck_id' => $truck->id,
                'motorista' => $truck->responsavel,
                'status' => 'montagem',
                'data_montagem' => $orderProduct->delivery_date . ' 08:00:00',
            ]
        );

        LoadItem::updateOrCreate(
            [
                'load_id' => $load->id,
                'order_product_id' => $orderProduct->id,
                'delivery_plan_id' => null,
            ],
            [
                'qtd_paletes' => 3,
                'zone_id' => $zone->id,
                'zona_nome' => null,
                'bairro' => $orderProduct->order->bairro,
            ]
        );

        $orderProduct->marcado_carga = 1;
        $orderProduct->save();
    }
}
