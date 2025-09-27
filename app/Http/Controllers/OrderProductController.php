<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Order;
use App\Seller;
use App\Client;
use App\Order_product;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
        $id = (int) $request->query('order');
        abort_if($id === 0, 404);

        $user_permissions = Helper::get_permissions();
        $order = Order::findOrFail($id);
        $order_products = Order_product::where('order_id', $order->order_number)->get();

        return view('order_products.order_products', compact('user_permissions', 'order', 'order_products'));
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

        $product = Product::firstOrCreate(['name' => trim($data['product_name'])]);
        $order = Order::find($request->input('order'));

        $order_product = new Order_product();
        $order_product->order_id = $order->order_number;
        $order_product->product_id = $product->id;
        $order_product->quant = str_replace('.', '', $data['quant']);
        $order_product->delivery_date = $data['delivery_date'];
        $order_product->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order_product->id, $order_product->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order]);
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

        return redirect()->route('order_products.index', ['order' => $data['order_id']]);
    }

    public function destroy(Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order = Order::where('order_number', $order_product->order_id)->first();
        $order_product->delete();
        Helper::saveLog(Auth::user()->id, 'Deleção', $order_product->id, $order_product->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order->id]);
    }
}
