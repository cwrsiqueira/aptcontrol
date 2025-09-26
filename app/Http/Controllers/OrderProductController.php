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

        return view('order_products.order_products', [
            'user_permissions' => $user_permissions,
            'order'            => Order::findOrFail($id),
            'order_products'   => Order_product::where('order_id', $id)->get(), // ou ->paginate(10)
        ]);
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
}
