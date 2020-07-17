<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;
use App\User;

class OrderController extends Controller
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

    public function get_permissions() {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (isset($_GET['comp'])) {
            $comp = 1;
        } else {
            $comp = 0;
        }

        $orders = Order::addSelect(['name_client' => Client::select('name')
        ->whereColumn('id', 'Orders.client_id')])
        ->where('complete_order', $comp)
        ->orderBy('order_date')
        ->orderBy('order_number')
        ->paginate(10);

        if (!empty($_GET['q'])) {

            $q = \DateTime::createFromFormat('d/m/Y', $_GET['q']);
            if($q && $q->format('d/m/Y') === $_GET['q']){
                $q = $_GET['q'];
                $q = explode('/', $q);
                $q = array_reverse($q);
                $q = implode('-', $q);
                // A consulta É por data
                $orders = Order::where('complete_order', $comp)
                ->where('order_date', $q)
                ->addSelect(['name_client' => Client::select('name')
                ->whereColumn('clients.id', 'Orders.client_id')])
                ->orderBy('order_date')
                ->orderBy('order_number')
                ->paginate(10);

                $q = date('d/m/Y', strtotime($q));

            } else {
                $q = $_GET['q'];
                // A consulta NÃO é por data

                if (ctype_alpha($q)) {
                    $clients = Client::where('name', 'LIKE', '%'.$q.'%')->get();
                    $client_group = array();
                    foreach ($clients as $item ) {
                        $client_group[] = $item->id;
                    }

                    $orders = Order::where('complete_order', $comp)
                    ->whereIn('client_id', $client_group)
                    ->addSelect(['name_client' => Client::select('name')
                    ->whereColumn('clients.id', 'Orders.client_id')])
                    ->orderBy('order_date')
                    ->orderBy('order_number')
                    ->paginate(10);
                } else {
                    $orders = Order::where('complete_order', $comp)
                    ->where('order_number', 'LIKE', '%'.$q.'%')
                    ->addSelect(['name_client' => Client::select('name')
                    ->whereColumn('clients.id', 'Orders.client_id')])
                    ->orderBy('order_date')
                    ->orderBy('order_number')
                    ->paginate(10);
                }
            }

        } else {
            $q = '';
        }

        $user_permissions = $this->get_permissions();
        
        return view('orders', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'orders' => $orders,
            'q' => $q,
            'comp' => $comp
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $client = array();
        if (!empty($_GET['client'])) {
            $client = Client::find($_GET['client']);
        }

        $products = Product::all();
        $user_permissions = $this->get_permissions();
        $seq_order_number = $this->get_seq_order_number();

       return view('orders_create', [
            'user' => Auth::user(),
            'client' => $client,
            'products' => $products,
            'user_permissions' => $user_permissions,
            'seq_order_number' => $seq_order_number,
       ]);
    }

    private function get_seq_order_number() {
        $items = array();
        $seq = 0;
        $sn_orders = Order::where('order_number', 'LIKE', '%sn%')->get('order_number');
        
        foreach ($sn_orders as $item) {
            $item = explode('-', $item->order_number);
            if (!empty($item[1])) {
                $items[] = $item[1];
            }
        }
        if (!empty($items)) {
            $seq = max($items);
        }
        
        $seq_order_number = 'sn-'.($seq+1);
        
        return $seq_order_number;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only([
            "order_date",
            "client_name",
            "client_id",
            "order_number",
            "total_order",
            "withdraw",
            "prod",
            "payment",
        ]);

        $validator = Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "client_name" => ['required'],
                "client_id" => ['required'],
                "order_number" => ['required', 'unique:orders'],
                "total_order" => ['required'],
                "withdraw" => ['required'],
                "prod" => ['required'],
                "payment" => ['required'],
            ]
        )->validate();

        $order_total = str_replace('.', '', $data['total_order']);
        $order_total = str_replace(',', '.', $order_total);

        $order = new Order();
        $order->client_id = $data['client_id'];
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->order_total = $order_total;
        $order->payment = $data['payment'];
        $order->withdraw = $data['withdraw'];
        $order->save();

        foreach($data['prod'] as $item) {
            if (!empty($item['product_name'])) {

                $quant = str_replace('.', '', $item['quant']);

                $unit_price = str_replace('.', '', $item['unit_val']);
                $unit_price = str_replace(',', '.', $unit_price);

                $total_price = str_replace('.', '', $item['total_val']);
                $total_price = str_replace(',', '.', $total_price);

                $order_prod = new Order_product();
                $order_prod->order_id = $data['order_number'];
                $order_prod->product_id = $item['product_name'];
                $order_prod->quant = $quant;
                $order_prod->unit_price = $unit_price;
                $order_prod->total_price = $total_price;
                $order_prod->delivery_date = $item['delivery_date'];
                $order_prod->save();
            }
        }

        return redirect()->route('orders.index', ['q' => $order_prod->order_id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::addSelect(['name_client' => Client::select('name')
        ->whereColumn('id', 'Orders.client_id')])->find($id);
        $order_products = Order_product::where('order_id', $order->order_number)->addSelect(['product_name' => Product::select('name')
        ->whereColumn('id', 'order_products.product_id')])->get();
        $user_permissions = $this->get_permissions();

        return view('orders_view',[
            'order' => $order,
            'order_products' => $order_products,
            'user_permissions' => $user_permissions
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $order = Order::addSelect(['name_client' => Client::select('name')
        ->whereColumn('id', 'Orders.client_id')])->find($id);
        $order_products = Order_product::where('order_id', $order->order_number)->addSelect(['product_name' => Product::select('name')
        ->whereColumn('id', 'order_products.product_id')])->get();
        $user_permissions = $this->get_permissions();
        $products = Product::all();

       return view('orders_edit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'order' => $order,
            'order_products' => $order_products,
            'products' => $products,
       ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            "order_date",
            "order_number",
            "total_order",
            "payment",
            "withdraw",
            "prod",
        ]);

        $validator = Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "order_number" => ['required'],
                "total_order" => ['required'],
                "payment" => ['required'],
                "withdraw" => ['required'],
                "prod" => ['required'],
            ]
        )->validate();
        
        $order_total = str_replace('.', '', $data['total_order']);
        $order_total = str_replace(',', '.', $order_total);

        $order = Order::find($id);
        $order->order_date = $data['order_date'];
        $order->order_total = $order_total;
        $order->payment = $data['payment'];
        $order->withdraw = $data['withdraw'];
        $order->save();

        Order_product::where('order_id', $data['order_number'])->delete();

        foreach($data['prod'] as $item) {
            if (!empty($item['product_name'])) {

                $quant = str_replace('.', '', $item['quant']);

                $unit_price = str_replace('.', '', $item['unit_val']);
                $unit_price = str_replace(',', '.', $unit_price);

                $total_price = str_replace('.', '', $item['total_val']);
                $total_price = str_replace(',', '.', $total_price);

                $order_prod = new Order_product();
                $order_prod->order_id = $data['order_number'];
                $order_prod->product_id = $item['product_name'];
                $order_prod->quant = $quant;
                $order_prod->unit_price = $unit_price;
                $order_prod->total_price = $total_price;
                $order_prod->delivery_date = $item['delivery_date'];
                $order_prod->save();
            }
        }

        return redirect()->route('orders.index', ['q' => $order_prod->order_id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
