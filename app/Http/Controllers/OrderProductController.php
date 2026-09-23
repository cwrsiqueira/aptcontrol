<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Order;
use App\Seller;
use App\Order_product;
use App\OrderProductDeliveryPlan;
use App\Product;
use App\LoadItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-pedidos');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-pedidos', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $order = Order::where('id', $request->input('order'))->with('client', 'seller')->first();

        $order_products = Order_product::where('order_id', $order->order_number)
            ->with('order', 'product', 'order.client', 'order.seller', 'deliveryPlans')
            ->withSaldo()
            ->orderBy('product_id')
            ->orderBy('quant')
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->get();

        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->select(
                'product_id',
                DB::raw('SUM(order_products.quant) as saldo'),
                DB::raw('SUM(CASE WHEN quant > 0 THEN quant ELSE 0 END) as saldo_inicial')
            )
            ->groupBy('product_id')
            ->get();

        // Filtra após calcular saldo (preserva comportamento do original)
        // $order_products = $order_products
        // ->where('saldo', '>=', 0)
        // ->where('delivery_date', '>', '1970-01-01');

        $total_products = $order_products->pluck('quant')->sum();
        if ($total_products > 0) {
            $order->update(['complete_order' => 0]);
        }

        return view('order_products.order_products', compact(
            'user_permissions',
            'order',
            'order_products',
            'saldo_produtos',
            'total_products',
        ));
    }

    public function create(Request $request)
    {
        $order = Order::find($request->input('order'));
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order])->withErrors($message);
        }

        $products = Product::all();
        return view('order_products.order_products_create', [
            'products' => $products,
            'user_permissions' => $user_permissions,
            'order' => $order,
        ]);
    }

    public function store(Request $request)
    {
        $order = Order::find($request->input('order'));
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order])->withErrors($message);
        }

        $data = $request->only([
            "product_name",
            "quant",
            "pallet_capacity",
            "delivery_date",
            "order",
            "delivery_plan",
        ]);

        $data['favorite_delivery'] = isset($data['favorite_delivery']) ? 1 : 0;

        Validator::make(
            $data,
            [
                "product_name" => ['required'],
                "quant" => ['required'],
                "pallet_capacity" => ['required', 'integer', 'min:1'],
                "delivery_date" => ['required'],
            ],
            [],
            [
                'product_name' => 'Produto',
                'pallet_capacity' => 'Capacidade do palete',
                'delivery_date' => 'Data de entrega',
            ]
        )->validate();

        // Calcula somente paletes completos pela capacidade informada.
        $quantity = (int) preg_replace('/\D+/', '', $data['quant']);
        $palletCapacity = (int) $data['pallet_capacity'];
        $minimumDeliveryDate = $data['delivery_date'];

        if ($quantity <= 0 || $quantity % $palletCapacity !== 0) {
            return redirect()->back()->withInput()->withErrors([
                'pallet_capacity' => 'A quantidade total deve preencher os paletes sem deixar espaço livre.',
            ]);
        }

        $deliveryPlanInput = array_map(function ($item) use ($palletCapacity) {
            $deliveryQuantity = (int) ($item['quantity'] ?? 0);
            $item['palete_tipo'] = [$palletCapacity];
            $item['palete_quant'] = [$deliveryQuantity > 0 ? intdiv($deliveryQuantity, $palletCapacity) : 0];

            return $item;
        }, $data['delivery_plan'] ?? []);
        $deliveryPlan = $this->validateDeliveryPlan($deliveryPlanInput, $quantity, $minimumDeliveryDate);
        $carga = json_encode($this->aggregatePalletLoads($deliveryPlan));

        $product = Product::firstOrCreate(['name' => trim($data['product_name'])], ['daily_production_forecast' => 0]);
        $order = Order::find($request->input('order'));

        $hasProduct = Order_product::where('order_id', $order->order_number)->where('product_id', $product->id)->count();
        if ($hasProduct > 0) {
            $message = [
                'has-product' => 'Produto já cadastrado pra esse pedido!',
            ];
            return redirect()->route('order_products.index', ['order' => $order->id])->withErrors($message);
        }

        // Salva o produto e suas entregas na mesma operação.
        $order_product = DB::transaction(function () use ($order, $product, $quantity, $deliveryPlan, $data, $carga) {
            $orderProduct = new Order_product();
            $orderProduct->order_id = $order->order_number;
            $orderProduct->product_id = $product->id;
            $orderProduct->quant = $quantity;
            $orderProduct->delivery_date = $data['delivery_date'];
            $orderProduct->favorite_delivery = $data['favorite_delivery'];
            $orderProduct->carga = $carga;
            $orderProduct->save();

            foreach ($deliveryPlan as $index => $item) {
                $orderProduct->deliveryPlans()->create([
                    'sequence' => $index + 1,
                    'quantity' => (int) $item['quantity'],
                    'delivery_date' => $item['date'],
                    'carga' => $item['carga'],
                ]);
            }

            return $orderProduct;
        });

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order_product->id, $order_product->order_number, 'Produtos Pedidos');

        return redirect()->route('order_products.index', ['order' => $order])->with('success', 'Salvo com sucesso!');
    }

    // Consulta as entregas previstas para a data.
    public function truckAvailability(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $planned = OrderProductDeliveryPlan::whereDate('delivery_date', $data['date'])
            ->whereHas('orderProduct.order', function ($query) {
                $query->where('withdraw', 'entregar')
                    ->where('complete_order', 0);
            })
            ->count();

        return response()->json([
            'planned' => $planned,
        ]);
    }

    public function edit(Request $request, Order_product $order_product)
    {
        $order = Order::find($request->input('order'));
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order])->withErrors($message);
        }

        $product_id = $request->input('product_id');

        $delivery_product = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $product_id)
            ->where('quant', '<', 0)
            ->count();

        $saldo = Order_product::where('order_id', $order_product->order_id)
            ->select(
                DB::raw('SUM(order_products.quant) as saldo'),
            )
            ->sum('quant');

        // if ($delivery_product > 0) {
        //     $message = ['has-order' => 'Produto do pedido possui entrega registrada e não pode ser editado!'];
        //     return redirect()->route('order_products.index', ['order' => $order_product->order->id])->withErrors($message);
        // }

        $products = Product::all();
        $sellers = Seller::all();
        $order = Order::where('order_number', $order_product->order_id)->first();

        $order_product->load('deliveryPlans');
        $carga = json_decode($order_product->carga, true);
        $palete = ['tipo' => [], 'quant' => []];
        if ($carga) {
            foreach ($carga as $k => $v) {
                $palete['tipo'][]  = (int) $k;
                $palete['quant'][] = (int) $v;
            }
        }

        $deliveryPlan = $order_product->deliveryPlans->map(function ($plan) {
            $carga = $plan->carga ?? [];

            return [
                'id' => $plan->id,
                'quantity' => (int) $plan->quantity,
                'date' => $plan->delivery_date->format('Y-m-d'),
                'palete_tipo' => array_map('intval', array_keys($carga)),
                'palete_quant' => array_map('intval', array_values($carga)),
            ];
        })->values()->all();

        // Usa os dados antigos quando ainda não existe planejamento.
        if (empty($deliveryPlan)) {
            $deliveryPlan[] = [
                'quantity' => (int) $order_product->quant,
                'date' => date('Y-m-d', strtotime($order_product->delivery_date)),
                'palete_tipo' => $palete['tipo'],
                'palete_quant' => $palete['quant'],
            ];
        }

        return view('order_products.order_products_edit', compact(
            'order_product',
            'products',
            'sellers',
            'user_permissions',
            'order',
            'palete',
            'deliveryPlan',
            'saldo',
        ));
    }

    public function update(Request $request, Order_product $order_product)
    {
        $order = Order::find($request->input('order'));
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order])->withErrors($message);
        }

        $data = $request->only([
            "quant",
            "delivery_date",
            "favorite_delivery",
            "order_id",
            "delivery_plan",
        ]);

        $data['favorite_delivery'] = isset($data['favorite_delivery']) ?: 0;
        $data['quant'] = isset($data['quant']) ? $data['quant'] : $order_product->quant;
        $data['delivery_date'] = isset($data['delivery_date']) ? $data['delivery_date'] : $order_product->delivery_date;

        Validator::make(
            $data,
            [
                "quant" => ['required'],
                "delivery_date" => ['required'],
                "favorite_delivery" => ['required'],
            ],
            [],
            [
                'delivery_date' => 'Data de entrega',
            ]
        )->validate();

        $order = Order::find($data['order_id']);
        $quantity = (int) preg_replace('/\D+/', '', $data['quant']);
        $planCount = $order_product->deliveryPlans()->count();
        $deliveryPlan = null;

        if (array_key_exists('delivery_plan', $data)) {
            $deliveryPlan = $this->validateDeliveryPlan(
                $data['delivery_plan'] ?? [],
                $quantity,
                $data['delivery_date']
            );

            // Protege entregas que já foram adicionadas a uma carga.
            $existingIds = $order_product->deliveryPlans()->pluck('id');
            $requestedIds = collect($deliveryPlan)->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $invalidIds = $requestedIds->diff($existingIds);
            $removedIds = $existingIds->diff($requestedIds);

            if ($invalidIds->isNotEmpty()) {
                return redirect()->back()->withInput()->withErrors([
                    'delivery_plan' => 'O planejamento informado não pertence a este produto.',
                ]);
            }

            if ($removedIds->isNotEmpty() && LoadItem::whereIn('delivery_plan_id', $removedIds)->exists()) {
                return redirect()->back()->withInput()->withErrors([
                    'delivery_plan' => 'Não é possível remover uma entrega que já está vinculada a uma carga.',
                ]);
            }

            foreach ($deliveryPlan as $index => $item) {
                if (empty($item['id'])) {
                    continue;
                }

                $existingPlan = $order_product->deliveryPlans()->find($item['id']);
                if (!$existingPlan || !$existingPlan->loadItems()->exists()) {
                    continue;
                }

                $sameQuantity = (float) $existingPlan->quantity === (float) $item['quantity'];
                $sameDate = $existingPlan->delivery_date->toDateString() === $item['date'];
                $sameLoad = $this->normalizedPalletLoad($existingPlan->carga) === $this->normalizedPalletLoad($item['carga']);

                if (!$sameQuantity || !$sameDate || !$sameLoad) {
                    return redirect()->back()->withInput()->withErrors([
                        "delivery_plan.{$index}" => 'Não é possível alterar uma entrega que já está vinculada a uma carga.',
                    ]);
                }
            }
        }

        if ($deliveryPlan === null) {
            $quantityValidator = Validator::make([], []);
            $quantityValidator->after(function ($validator) use ($quantity, $planCount, $order_product) {
                if ($planCount > 0 && $quantity % $planCount !== 0) {
                    $validator->errors()->add(
                        'quant',
                        "A quantidade deve ser divisível pelas {$planCount} entregas planejadas."
                    );
                }

                if ((int) $order_product->quant !== $quantity && $order_product->deliveryPlans()->whereHas('loadItems')->exists()) {
                    $validator->errors()->add('quant', 'Não é possível alterar a quantidade porque há entregas vinculadas a uma carga.');
                }
            });
            $quantityValidator->validate();
        }

        DB::transaction(function () use ($order_product, $order, $quantity, $data, $deliveryPlan, $planCount) {
            $order_product->order_id = $order->order_number;
            $order_product->quant = $quantity;
            $order_product->delivery_date = $data['delivery_date'];
            $order_product->favorite_delivery = $data['favorite_delivery'];

            if ($deliveryPlan !== null) {
                $order_product->carga = json_encode($this->aggregatePalletLoads($deliveryPlan));
            }

            $order_product->save();

            if ($deliveryPlan === null && $planCount > 0) {
                $order_product->deliveryPlans()->update([
                    'quantity' => $quantity / $planCount,
                ]);
            }

            if ($deliveryPlan !== null) {
                $keptIds = [];
                foreach ($deliveryPlan as $index => $item) {
                    $plan = !empty($item['id'])
                        ? $order_product->deliveryPlans()->findOrFail($item['id'])
                        : $order_product->deliveryPlans()->make();

                    $plan->sequence = $index + 1;
                    $plan->quantity = $item['quantity'];
                    $plan->delivery_date = $item['date'];
                    $plan->carga = $item['carga'];
                    $plan->save();
                    $keptIds[] = $plan->id;
                }

                $order_product->deliveryPlans()->whereNotIn('id', $keptIds)->delete();
            }
        });

        Helper::saveLog(Auth::user()->id, 'Alteração', $order_product->id, $order_product->order_number, 'Produtos Pedidos');

        return redirect()->route('order_products.index', ['order' => $data['order_id']])->with('success', 'Atualizado com sucesso!');
    }

    public function destroy(Order_product $order_product, Request $request)
    {
        $order = Order::find($request->input('order'));
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.delete', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order])->withErrors($message);
        }

        $main_order_product = $request->input('main_order_product');

        if (!$main_order_product) {
            $product_id = $request->input('product_id');

            $delivery_product = Order_product::where('order_id', $order_product->order_id)
                ->where('product_id', $product_id)
                ->where('quant', '<', 0)
                ->count();

            if ($delivery_product > 0) {
                $message = ['has-order' => 'Produto do pedido possui entrega registrada e não pode ser excluído!'];
                return redirect()->route('order_products.index', ['order' => $order_product->order->id])->withErrors($message);
            }
        }

        $order = Order::where('order_number', $order_product->order_id)->first();
        $order_product->delete();
        Helper::saveLog(Auth::user()->id, 'Deleção', $order_product->id, $order_product->order_number, 'Produtos Pedidos');

        if ($main_order_product)
            return redirect()->route('order_products.delivery', $main_order_product)->with('success', 'Excluído com sucesso!');
        else
            return redirect()->route('order_products.index', ['order' => $order->id])->with('success', 'Excluído com sucesso!');
    }

    public function delivery(Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.delivery', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order_product->order_id])->withErrors($message);
        }

        $delivered = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $order_product->product->id)
            ->where('quant', '<', 0)
            ->orderBy('delivery_date')
            ->get();

        // saldo total por produto (cabecalho)
        $saldo_produto = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $order_product->product_id)
            ->select('product_id', DB::raw('SUM(quant) as saldo'), DB::raw('SUM(CASE WHEN quant > 0 THEN quant ELSE 0 END) as saldo_inicial'))
            ->groupBy('product_id')
            ->first();

        return view('order_products.order_product_delivery', compact('order_product', 'user_permissions', 'delivered', 'saldo_produto'));
    }

    public function delivered(Request $request, Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('order_products.delivery', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('order_products.index', ['order' => $order_product->order_id])->withErrors($message);
        }

        $saldo_produto = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $order_product->product_id)
            ->select(DB::raw('SUM(quant) as saldo'))
            ->groupBy('product_id')
            ->first();

        $max_delivery = (int) $saldo_produto->saldo;
        $formated_max_delivery = number_format($max_delivery, 0, '', '.');
        $data['quant'] = (int) preg_replace('/\D+/', '', $request->input('quant', '0'));
        $data['delivery_date'] = $request->input('delivery_date', date('Y-m-d'));

        Validator::make(
            $data,
            [
                'quant'         => ['required', 'integer', 'min:1', "max:{$max_delivery}"],
                'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            ],
            [
                'delivery_date.after_or_equal' => 'A data de entrega deve ser hoje ou uma data futura.',
                'delivery_date.required'       => 'Informe a data de entrega.',
                'delivery_date.date'           => 'Informe uma data válida.',
                'quant.max'                    => "A quantidade a entregar não pode exceder o saldo de {$formated_max_delivery} disponível.",
            ],
            [
                'delivery_date' => 'Previsão de Entrega',
                'quant'         => 'Quantidade',
            ]
        )->validate();

        Order_product::create([
            "order_id" => $order_product->order_id,
            "product_id" => $order_product->product_id,
            "quant" => $data['quant'] * -1,
            "unit_price" => "0",
            "total_price" => "0",
            "delivery_date" => $data['delivery_date'],
            "favorite_delivery" => "0",
        ]);

        Helper::saveLog(Auth::user()->id, 'Entrega', $order_product->id, $order_product->order_number, 'Pedidos');

        return redirect()->route('order_products.delivery', $order_product->id)->with('success', 'Salvo com sucesso!');
    }

    // Valida quantidades, datas e paletes de cada entrega.
    private function validateDeliveryPlan(array $deliveryPlan, int $quantity, string $minimumDeliveryDate): array
    {
        $deliveryPlan = array_values($deliveryPlan);
        $validator = Validator::make(
            ['delivery_plan' => $deliveryPlan],
            [
                'delivery_plan' => ['required', 'array', 'min:1', 'max:100'],
                'delivery_plan.*.id' => ['nullable', 'integer'],
                'delivery_plan.*.quantity' => ['required', 'integer', 'min:1'],
                'delivery_plan.*.date' => ['required', 'date', 'after_or_equal:' . $minimumDeliveryDate, 'distinct'],
                'delivery_plan.*.palete_tipo' => ['nullable', 'array', 'max:3'],
                'delivery_plan.*.palete_tipo.*' => ['nullable', 'integer', 'min:1'],
                'delivery_plan.*.palete_quant' => ['nullable', 'array', 'max:3'],
                'delivery_plan.*.palete_quant.*' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'delivery_plan.required' => 'Defina o planejamento da entrega.',
                'delivery_plan.max' => 'O planejamento permite no máximo 100 entregas.',
                'delivery_plan.*.quantity.required' => 'Informe a quantidade de cada entrega.',
                'delivery_plan.*.date.required' => 'Informe a data de cada entrega.',
                'delivery_plan.*.date.after_or_equal' => 'As entregas não podem ser anteriores à previsão mínima.',
                'delivery_plan.*.date.distinct' => 'Cada entrega deve ter uma data diferente.',
                'delivery_plan.*.palete_tipo.*.integer' => 'A capacidade do palete deve ser um número inteiro.',
                'delivery_plan.*.palete_quant.*.integer' => 'A quantidade de paletes deve ser um número inteiro.',
            ]
        );

        $validator->after(function ($validator) use ($deliveryPlan, $quantity) {
            $quantities = array_map(fn ($item) => (int) ($item['quantity'] ?? 0), $deliveryPlan);

            if ($quantity <= 0 || array_sum($quantities) !== $quantity) {
                $validator->errors()->add('delivery_plan', 'A soma das entregas deve ser igual à quantidade do produto.');
            }

            if (count(array_unique($quantities)) > 1) {
                $validator->errors()->add('delivery_plan', 'O fracionamento deve ter quantidades iguais em todas as entregas.');
            }

            foreach ($deliveryPlan as $index => $item) {
                $types = $item['palete_tipo'] ?? [];
                $counts = $item['palete_quant'] ?? [];
                $slots = max(count($types), count($counts));
                $loadTotal = 0;
                $hasPallets = false;

                for ($slot = 0; $slot < $slots; $slot++) {
                    $hasType = !empty($types[$slot]);
                    $hasCount = !empty($counts[$slot]);
                    if ($hasType !== $hasCount) {
                        $validator->errors()->add(
                            "delivery_plan.{$index}.paletes",
                            'Informe a capacidade e a quantidade do palete.'
                        );
                    }

                    if ($hasType && $hasCount) {
                        $hasPallets = true;
                        $loadTotal += (int) $types[$slot] * (int) $counts[$slot];
                    }
                }

                if ($hasPallets && $loadTotal !== (int) ($item['quantity'] ?? 0)) {
                    $validator->errors()->add(
                        "delivery_plan.{$index}.paletes",
                        'A composição dos paletes deve preencher exatamente a quantidade da entrega.'
                    );
                }
            }
        });

        $validator->validate();

        return array_map(function ($item) {
            $item['carga'] = $this->buildPalletLoad(
                $item['palete_tipo'] ?? [],
                $item['palete_quant'] ?? []
            );

            return $item;
        }, $deliveryPlan);
    }

    private function buildPalletLoad(array $types, array $counts): array
    {
        $carga = [];
        foreach ($types as $index => $type) {
            $type = (int) $type;
            $count = (int) ($counts[$index] ?? 0);
            if ($type > 0 && $count > 0) {
                $carga[$type] = ($carga[$type] ?? 0) + $count;
            }
        }

        return $carga;
    }

    // Mantém o total antigo de paletes para compatibilidade.
    private function aggregatePalletLoads(array $deliveryPlan): array
    {
        $total = [];
        foreach ($deliveryPlan as $item) {
            foreach (($item['carga'] ?? []) as $type => $count) {
                $total[$type] = ($total[$type] ?? 0) + (int) $count;
            }
        }

        return $total;
    }

    private function normalizedPalletLoad(?array $load): array
    {
        $load = array_map('intval', $load ?? []);
        ksort($load);

        return $load;
    }
}
