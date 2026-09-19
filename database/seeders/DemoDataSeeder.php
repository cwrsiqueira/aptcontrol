<?php

namespace Database\Seeders;

use App\Client;
use App\Load;
use App\LoadItem;
use App\Order;
use App\Order_product;
use App\Product;
use App\ProductStock;
use App\Seller;
use App\Truck;
use App\Zone;
use App\ZoneBairro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Cria uma base completa para demonstração.
        DB::transaction(function () {
            $categories = $this->categories();
            $sellers = $this->sellers();
            $clients = $this->clients($categories);
            $products = $this->products();
            $zones = $this->zones();
            $trucks = $this->trucks();

            $this->orders($clients, $sellers, $products, $zones, $trucks);
        });
    }

    private function categories(): array
    {
        $categories = ['Padrão', 'Construtor', 'Revendedor', 'Consumidor final'];
        foreach ($categories as $name) {
            DB::table('clients_categories')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        return DB::table('clients_categories')->whereIn('name', $categories)->pluck('id', 'name')->all();
    }

    private function sellers(): array
    {
        $items = [
            ['name' => 'Mariana Costa', 'contact_type' => 'whatsapp', 'contact_value' => '(11) 90000-1001'],
            ['name' => 'Rafael Martins', 'contact_type' => 'telefone', 'contact_value' => '(11) 3000-2002'],
            ['name' => 'Camila Ribeiro', 'contact_type' => 'email', 'contact_value' => 'camila@example.test'],
        ];

        foreach ($items as $item) {
            Seller::updateOrCreate(['name' => $item['name']], $item);
        }

        return Seller::whereIn('name', array_column($items, 'name'))->get()->keyBy('name')->all();
    }

    private function clients(array $categories): array
    {
        $items = [
            ['name' => 'Construtora Horizonte', 'category' => 'Construtor', 'contact' => '(11) 90000-3001', 'address' => 'Rua das Acácias, 120 - Centro', 'favorite' => true],
            ['name' => 'Depósito Nova Base', 'category' => 'Revendedor', 'contact' => '(11) 90000-3002', 'address' => 'Avenida Industrial, 850 - Distrito Industrial', 'favorite' => true],
            ['name' => 'Obras Vale Verde', 'category' => 'Construtor', 'contact' => '(11) 90000-3003', 'address' => 'Rua do Bosque, 45 - Jardim Europa', 'favorite' => false],
            ['name' => 'Materiais São Lucas', 'category' => 'Revendedor', 'contact' => '(11) 90000-3004', 'address' => 'Avenida Norte, 310 - Vila Nova', 'favorite' => false],
            ['name' => 'Residencial Bela Vista', 'category' => 'Consumidor final', 'contact' => '(11) 90000-3005', 'address' => 'Rua das Palmeiras, 77 - Bela Vista', 'favorite' => false],
        ];

        foreach ($items as $item) {
            $client = Client::firstOrNew(['name' => $item['name']]);
            $client->id_categoria = $categories[$item['category']];
            $client->contact = $item['contact'];
            $client->full_address = $item['address'];
            $client->is_favorite = $item['favorite'];
            $client->save();
        }

        return Client::whereIn('name', array_column($items, 'name'))->get()->keyBy('name')->all();
    }

    private function products(): array
    {
        $items = [
            ['name' => 'Bloco de Concreto 14x19x39', 'stock' => 1800, 'forecast' => 600],
            ['name' => 'Bloco de Concreto 19x19x39', 'stock' => 950, 'forecast' => 420],
            ['name' => 'Canaleta de Concreto 14x19x39', 'stock' => 720, 'forecast' => 300],
            ['name' => 'Meio Bloco 14x19x19', 'stock' => 480, 'forecast' => 240],
            ['name' => 'Piso Intertravado 10x20', 'stock' => 2400, 'forecast' => 1000],
        ];

        foreach ($items as $item) {
            $product = Product::updateOrCreate(
                ['name' => $item['name']],
                ['current_stock' => $item['stock'], 'daily_production_forecast' => $item['forecast']]
            );
            ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'stock_date' => now()->toDateString()],
                ['stock' => $item['stock'], 'notes' => 'Estoque inicial da demonstração']
            );
        }

        return Product::whereIn('name', array_column($items, 'name'))->get()->keyBy('name')->all();
    }

    private function zones(): array
    {
        $items = [
            'Centro' => ['Centro', 'Bela Vista'],
            'Norte' => ['Vila Nova', 'Jardim Primavera'],
            'Sul' => ['Jardim Europa', 'Parque das Flores'],
            'Industrial' => ['Distrito Industrial'],
        ];

        foreach ($items as $name => $neighborhoods) {
            $zone = Zone::updateOrCreate(['nome' => $name], ['obs' => 'Zona de demonstração']);
            foreach ($neighborhoods as $neighborhood) {
                ZoneBairro::firstOrCreate(['zone_id' => $zone->id, 'bairro_nome' => $neighborhood]);
            }
        }

        return Zone::whereIn('nome', array_keys($items))->get()->keyBy('nome')->all();
    }

    private function trucks(): array
    {
        $items = [
            ['responsavel' => 'João Almeida', 'capacidade_paletes' => 10, 'modelo' => 'Mercedes-Benz Accelo', 'placa' => 'DEM-1001'],
            ['responsavel' => 'Carlos Nunes', 'capacidade_paletes' => 12, 'modelo' => 'Volkswagen Delivery', 'placa' => 'DEM-1002'],
            ['responsavel' => 'Paulo Mendes', 'capacidade_paletes' => 14, 'modelo' => 'Iveco Tector', 'placa' => 'DEM-1003'],
        ];

        foreach ($items as $item) {
            Truck::updateOrCreate(['placa' => $item['placa']], $item);
        }

        return Truck::whereIn('placa', array_column($items, 'placa'))->get()->keyBy('placa')->all();
    }

    private function orders(array $clients, array $sellers, array $products, array $zones, array $trucks): void
    {
        // Monta pedidos com entregas e paletes variados.
        $orders = [
            [
                'number' => 'PED-DEMO-1001', 'client' => 'Construtora Horizonte', 'seller' => 'Mariana Costa',
                'days_ago' => 12, 'withdraw' => 'entregar', 'address' => 'Rua das Acácias, 120',
                'neighborhood' => 'Centro', 'zone' => 'Centro', 'payment' => 'Aberto',
                'items' => [
                    ['product' => 'Bloco de Concreto 14x19x39', 'quantity' => 1170, 'unit_price' => 3.50, 'start' => 1, 'parts' => 3, 'pallets' => [390 => 1]],
                    ['product' => 'Canaleta de Concreto 14x19x39', 'quantity' => 650, 'unit_price' => 5.20, 'start' => 2, 'parts' => 1, 'pallets' => [390 => 1, 260 => 1]],
                ],
            ],
            [
                'number' => 'PED-DEMO-1002', 'client' => 'Depósito Nova Base', 'seller' => 'Rafael Martins',
                'days_ago' => 8, 'withdraw' => 'entregar', 'address' => 'Avenida Industrial, 850',
                'neighborhood' => 'Distrito Industrial', 'zone' => 'Industrial', 'payment' => 'Parcial',
                'items' => [
                    ['product' => 'Bloco de Concreto 19x19x39', 'quantity' => 1040, 'unit_price' => 6.10, 'start' => 2, 'parts' => 2, 'pallets' => [260 => 2]],
                    ['product' => 'Meio Bloco 14x19x19', 'quantity' => 650, 'unit_price' => 2.80, 'start' => 3, 'parts' => 2, 'pallets' => [325 => 1]],
                ],
            ],
            [
                'number' => 'PED-DEMO-1003', 'client' => 'Obras Vale Verde', 'seller' => 'Camila Ribeiro',
                'days_ago' => 5, 'withdraw' => 'retirar', 'address' => null,
                'neighborhood' => null, 'zone' => null, 'payment' => 'Aberto',
                'items' => [
                    ['product' => 'Piso Intertravado 10x20', 'quantity' => 780, 'unit_price' => 4.50, 'start' => 1, 'parts' => 1, 'pallets' => [390 => 2]],
                ],
            ],
            [
                'number' => 'PED-DEMO-1004', 'client' => 'Materiais São Lucas', 'seller' => 'Mariana Costa',
                'days_ago' => 18, 'withdraw' => 'entregar', 'address' => 'Avenida Norte, 310',
                'neighborhood' => 'Vila Nova', 'zone' => 'Norte', 'payment' => 'Total',
                'completed' => true,
                'items' => [
                    ['product' => 'Bloco de Concreto 14x19x39', 'quantity' => 780, 'unit_price' => 3.50, 'start' => -3, 'parts' => 2, 'pallets' => [390 => 1]],
                ],
            ],
            [
                'number' => 'PED-DEMO-1005', 'client' => 'Residencial Bela Vista', 'seller' => 'Rafael Martins',
                'days_ago' => 10, 'withdraw' => 'entregar', 'address' => 'Rua das Palmeiras, 77',
                'neighborhood' => 'Bela Vista', 'zone' => 'Centro', 'payment' => 'Aberto',
                'items' => [
                    ['product' => 'Canaleta de Concreto 14x19x39', 'quantity' => 650, 'unit_price' => 5.20, 'start' => -1, 'parts' => 1, 'pallets' => [390 => 1, 260 => 1]],
                ],
            ],
        ];

        $firstLoadPlan = null;
        $firstLoadOrderProduct = null;

        foreach ($orders as $orderData) {
            $order = Order::firstOrNew(['order_number' => $orderData['number']]);
            $order->client_id = $clients[$orderData['client']]->id;
            $order->seller_id = $sellers[$orderData['seller']]->id;
            $order->order_date = now()->subDays($orderData['days_ago'])->toDateString();
            $order->order_total = array_sum(array_map(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            }, $orderData['items']));
            $order->payment = $orderData['payment'];
            $order->withdraw = $orderData['withdraw'];
            $order->endereco = $orderData['address'];
            $order->bairro = $orderData['neighborhood'];
            $order->zona = $orderData['zone'];
            $order->complete_order = 0;
            $order->save();

            foreach ($orderData['items'] as $itemData) {
                $product = $products[$itemData['product']];
                $perDelivery = intdiv($itemData['quantity'], $itemData['parts']);
                $palletUnitTotal = 0;
                $aggregateLoad = [];
                foreach ($itemData['pallets'] as $capacity => $palletCount) {
                    $palletUnitTotal += $capacity * $palletCount;
                    $aggregateLoad[$capacity] = $palletCount * $itemData['parts'];
                }

                if ($palletUnitTotal !== $perDelivery) {
                    throw new \LogicException("A composição de paletes não fecha a entrega de {$itemData['product']}.");
                }

                $baseDate = now()->addDays($itemData['start']);

                $orderProduct = Order_product::where('order_id', $order->order_number)
                    ->where('product_id', $product->id)
                    ->where('quant', '>', 0)
                    ->first() ?? new Order_product();
                $orderProduct->order_id = $order->order_number;
                $orderProduct->product_id = $product->id;
                $orderProduct->quant = $itemData['quantity'];
                $orderProduct->unit_price = $itemData['unit_price'];
                $orderProduct->total_price = $itemData['quantity'] * $itemData['unit_price'];
                $orderProduct->delivery_date = $baseDate->toDateString();
                $orderProduct->favorite_delivery = $itemData['start'] <= 1;
                $orderProduct->checkmark = $itemData['start'] <= 0 ? 1 : 2;
                $orderProduct->carga = json_encode($aggregateLoad);
                $orderProduct->save();

                for ($sequence = 1; $sequence <= $itemData['parts']; $sequence++) {
                    $plan = $orderProduct->deliveryPlans()->updateOrCreate(
                        ['sequence' => $sequence],
                        [
                            'quantity' => $perDelivery,
                            'delivery_date' => $baseDate->copy()->addDays($sequence - 1)->toDateString(),
                            'carga' => $itemData['pallets'],
                        ]
                    );

                    if ($order->order_number === 'PED-DEMO-1001' && $sequence === 1 && !$firstLoadPlan) {
                        $firstLoadPlan = $plan;
                        $firstLoadOrderProduct = $orderProduct;
                    }
                }

                if (!empty($orderData['completed'])) {
                    $delivered = Order_product::where('order_id', $order->order_number)
                        ->where('product_id', $product->id)
                        ->where('quant', '<', 0)
                        ->first() ?? new Order_product();
                    $delivered->order_id = $order->order_number;
                    $delivered->product_id = $product->id;
                    $delivered->quant = $itemData['quantity'] * -1;
                    $delivered->delivery_date = now()->subDays(2)->toDateString();
                    $delivered->unit_price = 0;
                    $delivered->total_price = 0;
                    $delivered->save();
                }
            }

            $order->update(['complete_order' => !empty($orderData['completed']) ? 1 : 0]);
        }

        if ($firstLoadPlan && $firstLoadOrderProduct) {
            $loadDate = $firstLoadPlan->delivery_date->toDateString();
            $load = Load::where('truck_id', $trucks['DEM-1001']->id)
                ->where('status', 'montagem')
                ->whereDate('data_montagem', $loadDate)
                ->first();

            if (!$load) {
                $load = Load::create([
                    'truck_id' => $trucks['DEM-1001']->id,
                    'status' => 'montagem',
                    'data_montagem' => $loadDate,
                    'motorista' => $trucks['DEM-1001']->responsavel,
                ]);
            }

            LoadItem::updateOrCreate(
                [
                    'load_id' => $load->id,
                    'order_product_id' => $firstLoadOrderProduct->id,
                    'delivery_plan_id' => $firstLoadPlan->id,
                ],
                [
                    'qtd_paletes' => $firstLoadPlan->total_paletes,
                    'zone_id' => $zones['Centro']->id,
                    'zona_nome' => null,
                    'bairro' => $firstLoadOrderProduct->order->bairro,
                ]
            );
        }
    }
}
