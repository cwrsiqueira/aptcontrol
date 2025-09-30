<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Order;
use App\Seller;
use App\Order_product;
use App\Product;
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
        $this->middleware('can:menu-produtos-pedidos');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-produtos-pedidos', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $order = Order::where('id', $request->input('order'))->with('client', 'seller')->first();

        $order_products = Order_product::where('order_id', $order->order_number)
            ->with('order', 'product', 'order.client', 'order.seller')
            ->orderBy('delivery_date')
            ->get();

        $delivery_products = $order_products->where('quant', '<', 0)->count();

        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->select('product_id', DB::raw('SUM(order_products.quant) as saldo'))
            ->groupBy('product_id')
            ->get();

        // Recalcular "saldo" acumulado por pedido (mesma regra: min(acumulado, quant da linha))
        $acc = [];
        foreach ($order_products as $k => $row) {
            $pedido = $row->order_id;
            $acc[$pedido] = ($acc[$pedido] ?? 0) + $row->quant;
            $order_products[$k]->saldo = ($acc[$pedido] > $row->quant) ? $row->quant : $acc[$pedido];
        }

        // Filtra após calcular saldo (preserva comportamento do original)
        $order_products = $order_products
            ->where('saldo', '>', 0)
            ->where('delivery_date', '>', '1970-01-01');

        foreach ($order_products as $key => $op) {
            foreach ($saldo_produtos as $sp) {
                if ($op->product_id === $sp->product_id) {
                    $order_products[$key]['saldo'] = $sp->saldo > $op->quant ? $op->quant : $sp->saldo;
                }
            }
        }

        return view('order_products.order_products', compact(
            'user_permissions',
            'order',
            'order_products',
            'saldo_produtos',
            'delivery_products'
        ));
    }

    public function indexBackup(Request $request)
    {
        $id = (int) $request->query('order');
        abort_if($id === 0, 404);

        $user_permissions = Helper::get_permissions();
        $order = Order::findOrFail($id);

        $order_products = Order_product::where('order_id', $order->order_number)
            ->orderBy('product_id')
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->get();

        $acc = [];
        foreach ($order_products as $k => $row) {
            $product = $row->product_id;
            $acc[$product] = ($acc[$product] ?? 0) + $row->quant;
            $order_products[$k]->saldo = ($acc[$product] > $row->quant) ? $row->quant : $acc[$product];
        }

        $order_products = $order_products
            ->where('saldo', '>=', 0)
            ->where('delivery_date', '>', '1970-01-01');

        // saldo total por produto (cabecalho)
        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->select('product_id', DB::raw('SUM(quant) as saldo'), DB::raw('SUM(CASE WHEN quant > 0 THEN quant ELSE 0 END) as saldo_positivo'))
            ->groupBy('product_id')
            ->get();

        $delivery_products = Order_product::where('order_id', $order->order_number)
            ->where('quant', '<', 0)
            ->count();

        return view('order_products.order_products', compact('user_permissions', 'order', 'order_products', 'saldo_produtos', 'delivery_products'));
    }

    public function create(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }
        $products = Product::all();
        return view('order_products.order_products_create', [
            'products' => $products,
            'user_permissions' => $user_permissions,
            'order' => Order::find($request->input('order')),
        ]);
    }

    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $data = $request->only([
            "product_name",
            "quant",
            "delivery_date",
            "order",
        ]);

        $data['favorite_delivery'] = isset($data['favorite_delivery']) ? 1 : 0;

        Validator::make(
            $data,
            [
                "product_name" => ['required'],
                "quant" => ['required'],
                "delivery_date" => ['required'],
            ],
            [],
            [
                'product_name' => 'Produto',
                'delivery_date' => 'Data de entrega',
            ]
        )->validate();

        $product = Product::firstOrCreate(['name' => trim($data['product_name'])], ['daily_production_forecast' => 0]);
        $order = Order::find($request->input('order'));

        $order_product = new Order_product();
        $order_product->order_id = $order->order_number;
        $order_product->product_id = $product->id;
        $order_product->quant = str_replace('.', '', $data['quant']);
        $order_product->delivery_date = $data['delivery_date'];
        $order_product->favorite_delivery = $data['favorite_delivery'];
        $order_product->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order_product->id, $order_product->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order])->with('success', 'Salvo com sucesso!');
    }

    public function edit(Request $request, Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $delivery_product = Order_product::where('order_id', $order_product->order_id)
            ->where('quant', '<', 0)
            ->count();

        if ($delivery_product > 0) {
            $message = ['has-order' => 'Produto do pedido possui entrega registrada e não pode ser editado!'];
            return redirect()->route('order_products.index', ['order' => $order_product->order->id])->withErrors($message);
        }

        $products = Product::all();
        $sellers = Seller::all();
        $order = Order::where('order_number', $order_product->order_id)->first();

        return view('order_products.order_products_edit', compact(
            'order_product',
            'products',
            'sellers',
            'user_permissions',
            'order',
        ));
    }

    public function update(Request $request, Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $data = $request->only([
            "product_name",
            "quant",
            "delivery_date",
            "favorite_delivery",
            "order_id",
        ]);

        $data['favorite_delivery'] = isset($data['favorite_delivery']) ? 1 : 0;

        Validator::make(
            $data,
            [
                "product_name" => ['required'],
                "quant" => ['required'],
                "delivery_date" => ['required'],
                "favorite_delivery" => ['required'],
            ],
            [],
            [
                'product_name' => 'Produto',
                'delivery_date' => 'Data de entrega',
            ]
        )->validate();

        $product = Product::firstOrCreate(
            ['name' => trim($data['product_name'])],
            ['daily_production_forecast' => 0],
        );
        $order = Order::find($data['order_id']);

        $order_product = Order_product::find($order_product->id);
        $order_product->order_id = $order->order_number;
        $order_product->product_id = $product->id;
        $order_product->quant = str_replace('.', '', $data['quant']);
        $order_product->delivery_date = $data['delivery_date'];
        $order_product->favorite_delivery = $data['favorite_delivery'];
        $order_product->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order_product->id, $order_product->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $data['order_id']])->with('success', 'Atualizado com sucesso!');
    }

    public function destroy(Order_product $order_product, Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $main_order_product = $request->input('main_order_product');

        if (!$main_order_product) {
            $delivery_product = Order_product::where('order_id', $order_product->order_id)
                ->where('quant', '<', 0)
                ->count();

            if ($delivery_product > 0) {
                $message = ['has-order' => 'Produto do pedido possui entrega registrada e não pode ser excluído!'];
                return redirect()->route('order_products.index', ['order' => $order_product->order->id])->withErrors($message);
            }
        }

        $order = Order::where('order_number', $order_product->order_id)->first();
        $order_product->delete();
        Helper::saveLog(Auth::user()->id, 'Deleção', $order_product->id, $order_product->order_number, 'Pedidos');

        if ($main_order_product)
            return redirect()->route('order_products.delivery', $main_order_product)->with('success', 'Excluído com sucesso!');
        else
            return redirect()->route('order_products.index', ['order' => $order->id])->with('success', 'Excluído com sucesso!');
    }

    public function delivery(Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $delivered = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $order_product->product->id)
            ->where('quant', '<', 0)
            ->orderBy('delivery_date')
            ->get();

        // saldo total por produto (cabecalho)
        $saldo_produto = Order_product::where('order_id', $order_product->order_id)
            ->where('product_id', $order_product->product_id)
            ->select('product_id', DB::raw('SUM(quant) as saldo'), DB::raw('SUM(CASE WHEN quant > 0 THEN quant ELSE 0 END) as total_pedido'))
            ->groupBy('product_id')
            ->first();

        return view('order_products.order_product_delivery', compact('order_product', 'user_permissions', 'delivered', 'saldo_produto'));
    }

    public function delivered(Request $request, Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
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
                'quant.max'                    => "A quantidade a entregar não pode exceder {$formated_max_delivery}.",
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
}
