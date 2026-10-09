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
            "quantity_per_delivery",
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
                "quantity_per_delivery" => ['required', 'integer', 'min:1'],
                "delivery_date" => ['required'],
            ],
            [],
            [
                'product_name' => 'Produto',
                'quantity_per_delivery' => 'Quantidade por entrega',
                'delivery_date' => 'Data de entrega',
            ]
        )->validate();

        // Monta lotes cheios e deixa a sobra na última entrega.
        $quantity = (int) preg_replace('/\D+/', '', $data['quant']);
        $perDelivery = (int) $data['quantity_per_delivery'];
        $minimumDeliveryDate = $data['delivery_date'];

        if ($quantity <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'quant' => 'Informe a quantidade.',
            ]);
        }

        $submittedPlan = array_values($data['delivery_plan'] ?? []);
        $submittedQuantities = $this->submittedQuantities($submittedPlan);
        if ($this->quantitiesMatchTotal($submittedQuantities, $quantity)) {
            if (count($submittedQuantities) > 100) {
                return redirect()->back()->withInput()->withErrors([
                    'delivery_plan' => 'O planejamento permite no máximo 100 entregas.',
                ]);
            }
            $lots = $submittedQuantities;
        } else {
            $deliveryCount = $this->deliveryCount($quantity, $perDelivery);
            if ($deliveryCount > 100) {
                return redirect()->back()->withInput()->withErrors([
                    'quantity_per_delivery' => 'A quantidade por entrega gera mais de 100 entregas. Aumente a quantidade por entrega.',
                ]);
            }
            $lots = $this->deliveryLots($quantity, $perDelivery);
        }

        $deliveryPlanInput = $this->planFromLots(
            $lots,
            $submittedPlan,
            $minimumDeliveryDate,
            true
        );
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
                'pallet_capacity' => $this->inferPalletCapacity($carga, (int) $plan->quantity),
                'palete_tipo' => array_map('intval', array_keys($carga)),
                'palete_quant' => array_map('intval', array_values($carga)),
            ];
        })->values()->all();

        // Usa os dados antigos quando ainda não existe planejamento.
        if (empty($deliveryPlan)) {
            $legacyLoad = [];
            foreach ($palete['tipo'] as $index => $type) {
                $count = (int) ($palete['quant'][$index] ?? 0);
                if ((int) $type > 0 && $count > 0) {
                    $legacyLoad[(int) $type] = ($legacyLoad[(int) $type] ?? 0) + $count;
                }
            }

            $deliveryPlan[] = [
                'quantity' => (int) $order_product->quant,
                'date' => date('Y-m-d', strtotime($order_product->delivery_date)),
                'pallet_capacity' => $this->inferPalletCapacity($legacyLoad, (int) $order_product->quant),
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
            "quantity_per_delivery",
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
        $perDelivery = array_key_exists('quantity_per_delivery', $data)
            ? (int) $data['quantity_per_delivery']
            : null;

        if ($perDelivery !== null && $perDelivery < 1) {
            return redirect()->back()->withInput()->withErrors([
                'quantity_per_delivery' => 'Informe a quantidade por entrega.',
            ]);
        }

        if (array_key_exists('delivery_plan', $data)) {
            if ($quantity <= 0) {
                return redirect()->back()->withInput()->withErrors([
                    'quant' => 'Informe a quantidade.',
                ]);
            }

            $submittedPlan = array_values($data['delivery_plan'] ?? []);
            $submittedQuantities = $this->submittedQuantities($submittedPlan);
            if ($this->quantitiesMatchTotal($submittedQuantities, $quantity)) {
                if (count($submittedQuantities) > 100) {
                    return redirect()->back()->withInput()->withErrors([
                        'delivery_plan' => 'O planejamento permite no máximo 100 entregas.',
                    ]);
                }
                $lots = $submittedQuantities;
                $singlePallet = false;
            } elseif ($perDelivery !== null) {
                $deliveryCount = $this->deliveryCount($quantity, $perDelivery);
                if ($deliveryCount > 100) {
                    return redirect()->back()->withInput()->withErrors([
                        'quantity_per_delivery' => 'A quantidade por entrega gera mais de 100 entregas. Aumente a quantidade por entrega.',
                    ]);
                }
                $lots = $this->deliveryLots($quantity, $perDelivery);
                $singlePallet = true;
            } else {
                $lots = null;
                $singlePallet = false;
            }

            if ($lots !== null) {
                $data['delivery_plan'] = $this->planFromLots(
                    $lots,
                    $submittedPlan,
                    $data['delivery_date'],
                    $singlePallet
                );
            }
        }

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
                if ($planCount > 0 && $quantity < $planCount) {
                    $validator->errors()->add(
                        'quant',
                        'A quantidade precisa ser de pelo menos um produto por entrega.'
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
                $lots = $this->remainderOnLast($quantity, $planCount);
                $plans = $order_product->deliveryPlans()->orderBy('sequence')->orderBy('id')->get();
                $changed = $plans->contains(function ($plan, $index) use ($lots) {
                    return (int) $plan->quantity !== $lots[$index];
                });

                foreach ($plans as $index => $plan) {
                    $plan->quantity = $lots[$index];
                    if ($changed) {
                        $plan->carga = [$lots[$index] => 1];
                    }
                    $plan->save();
                }

                if ($changed) {
                    $order_product->carga = json_encode($this->aggregatePalletLoads(
                        $plans->map(fn ($plan) => ['carga' => $plan->carga ?? []])->all()
                    ));
                    $order_product->save();
                }
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

    // Conta as entregas cheias e a última com sobra.
    private function deliveryCount(int $quantity, int $perDelivery): int
    {
        if ($quantity <= 0 || $perDelivery <= 0 || $perDelivery >= $quantity) {
            return 1;
        }

        $remainder = $quantity % $perDelivery;

        return intdiv($quantity, $perDelivery) + ($remainder > 0 ? 1 : 0);
    }

    // Separa a quantidade em lotes iguais e deixa o resto na última posição.
    private function deliveryLots(int $quantity, int $perDelivery): array
    {
        if ($quantity <= 0 || $perDelivery <= 0 || $perDelivery >= $quantity) {
            return [$quantity];
        }

        $fullCount = intdiv($quantity, $perDelivery);
        $remainder = $quantity % $perDelivery;
        $lots = array_fill(0, $fullCount, $perDelivery);
        if ($remainder > 0) {
            $lots[] = $remainder;
        }

        return $lots;
    }

    // Distribui uma nova quantidade nas entregas já existentes, com a sobra na última.
    private function remainderOnLast(int $quantity, int $deliveryCount): array
    {
        $base = intdiv($quantity, $deliveryCount);
        $remainder = $quantity % $deliveryCount;
        $lots = array_fill(0, $deliveryCount, $base);
        $lots[$deliveryCount - 1] = $base + $remainder;

        return $lots;
    }

    private function dateAfter(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' 12:00:00 +' . $days . ' days'));
    }

    private function isSunday(string $date): bool
    {
        return date('w', strtotime($date . ' 12:00:00')) === '0';
    }

    // Domingo calculado passa para a segunda-feira.
    private function nextBusinessDay(string $date): string
    {
        return $this->isSunday($date) ? $this->dateAfter($date, 1) : $date;
    }

    private function businessDayAfter(string $date): string
    {
        return $this->nextBusinessDay($this->dateAfter($date, 1));
    }

    private function submittedQuantities(array $submitted): array
    {
        return array_map(fn ($item) => (int) ($item['quantity'] ?? 0), array_values($submitted));
    }

    private function quantitiesMatchTotal(array $quantities, int $total): bool
    {
        if ($quantities === [] || $total <= 0 || min($quantities) < 1) {
            return false;
        }

        return array_sum($quantities) === $total;
    }

    // Aplica os lotes calculados e usa as datas enviadas pelo formulário.
    private function planFromLots(array $lots, array $submitted, string $minimumDate, bool $singlePallet): array
    {
        $submitted = array_values($submitted);
        $plan = [];
        $previousDate = null;

        foreach ($lots as $index => $lot) {
            $source = $submitted[$index] ?? [];
            if (!empty($source['date'])) {
                $date = $source['date'];
            } elseif ($previousDate === null) {
                $date = $this->nextBusinessDay($minimumDate);
            } else {
                $date = $this->businessDayAfter($previousDate);
            }
            $previousDate = $date;

            $item = [
                'quantity' => $lot,
                'date' => $date,
            ];

            if (!empty($source['id'])) {
                $item['id'] = $source['id'];
            }

            $capacity = (int) preg_replace('/\D+/', '', (string) ($source['pallet_capacity'] ?? ''));
            if ($capacity > 0) {
                $split = $this->palletSplit($lot, $capacity);
                $item['palete_tipo'] = $split['types'];
                $item['palete_quant'] = $split['counts'];
            } elseif ($singlePallet || empty($source['palete_tipo'])) {
                $item['palete_tipo'] = [$lot];
                $item['palete_quant'] = [1];
            } else {
                $item['palete_tipo'] = $source['palete_tipo'];
                $item['palete_quant'] = $source['palete_quant'] ?? [];
            }

            $plan[] = $item;
        }

        return $plan;
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

            foreach ($deliveryPlan as $index => $item) {
                if (!empty($item['date']) && $this->isSunday($item['date'])) {
                    $validator->errors()->add(
                        "delivery_plan.{$index}.date",
                        'Não é possível agendar entrega no domingo.'
                    );
                }
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

    // Divide a quantidade da entrega em paletes cheios e, se sobrar, mais um palete.
    private function palletSplit(int $quantity, int $capacity): array
    {
        if ($capacity < 1 || $capacity >= $quantity) {
            return [
                'types' => [$quantity],
                'counts' => [1],
            ];
        }

        $full = intdiv($quantity, $capacity);
        $remainder = $quantity % $capacity;
        if ($remainder === 0) {
            return [
                'types' => [$capacity],
                'counts' => [$full],
            ];
        }

        return [
            'types' => [$capacity, $remainder],
            'counts' => [$full, 1],
        ];
    }

    // Recupera a capacidade cheia quando a carga da entrega já está dividida.
    private function inferPalletCapacity(array $carga, int $quantity): ?int
    {
        $carga = array_filter(array_map('intval', $carga));
        if (count($carga) === 1) {
            $type = (int) array_key_first($carga);
            $count = (int) reset($carga);
            if ($count > 1 && $type * $count === $quantity) {
                return $type;
            }

            return null;
        }

        if (count($carga) >= 2) {
            return max(array_map('intval', array_keys($carga)));
        }

        return null;
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
