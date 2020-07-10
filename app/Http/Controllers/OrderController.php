<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;

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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::addSelect(['name_client' => Client::select('name')
        ->whereColumn('id', 'Orders.client_id')])->paginate(5);
        $q = '';
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            
            $clients = Client::where('name', 'LIKE', '%'.$q.'%')->get();
            $client_group = array();
            foreach ($clients as $item ) {
                $client_group[] = $item->id;
            }
            
            $orders = Order::addSelect(['name_client' => Client::select('name')
            ->whereColumn('clients.id', 'Orders.client_id')])
            ->where('orders.order_number', 'LIKE', '%'.$q.'%')
            ->orWhere('orders.order_date', 'LIKE', '%'.$q.'%')
            ->orwhereIn('orders.client_id', $client_group)
            ->paginate(5);
            // ->toSql();
            // dd($orders);
        }
        
        return view('orders', [
            'user' => Auth::user(),
            'orders' => $orders,
            'q' => $q
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

       return view('orders_create', [
            'user' => Auth::user(),
            'client' => $client,
            'products' => $products
       ]);
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
            "product_name1",
            "quant1",
            "unit_val1",
            "total_val1",
            "delivery_date1",
            "product_name2",
            "quant2",
            "unit_val2",
            "total_val2",
            "delivery_date2",
            "product_name3",
            "quant3",
            "unit_val3",
            "total_val3",
            "delivery_date3",
            "product_name4",
            "quant4",
            "unit_val4",
            "total_val4",
            "delivery_date4",
        ]);

        $validator = Validator::make(
            $data,
            [
                "order_date" => [],
                "client_name" => [],
                "client_id" => [],
                "order_number" => ['unique:orders'],
                "total_order" => [],
                "withdraw" => [],
                "product_name1" => [],
                "quant1" => [],
                "unit_val1" => [],
                "total_val1" => [],
                "delivery_date1" => [],
                "product_name2" => [],
                "quant2" => [],
                "unit_val2" => [],
                "total_val2" => [],
                "delivery_date2" => [],
                "product_name3" => [],
                "quant3" => [],
                "unit_val3" => [],
                "total_val3" => [],
                "delivery_date3" => [],
                "product_name4" => [],
                "quant4" => [],
                "unit_val4" => [],
                "total_val4" => [],
                "delivery_date4" => [],
            ]
        )->validate();

        $order_total = str_replace('.', '', $data['total_order']);
        $order_total = str_replace(',', '.', $order_total);

        $order = new Order();
        $order->client_id = $data['client_id'];
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->order_total = $order_total;
        $order->payment = 'Aberto';
        $order->withdraw = $data['withdraw'];
        $order->save();

        for ($i=1; $i < 5; $i++) { 
            if (!empty($data['product_name'.$i])) {

                $quant = str_replace('.', '', $data['quant'.$i]);

                $unit_price = str_replace('.', '', $data['unit_val'.$i]);
                $unit_price = str_replace(',', '.', $unit_price);

                $total_price = str_replace('.', '', $data['total_val'.$i]);
                $total_price = str_replace(',', '.', $total_price);

                $order_prod = new Order_product();
                $order_prod->order_id = $data['order_number'];
                $order_prod->product_id = $data['product_name'.$i];
                $order_prod->quant = $quant;
                $order_prod->unit_price = $unit_price;
                $order_prod->total_price = $total_price;
                $order_prod->delivery_date = $data['delivery_date'.$i];
                $order_prod->save();
            }
        }

        return redirect()->route('orders.index');
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

        return view('orders_view',[
            'order' => $order,
            'order_products' => $order_products
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
        //
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
        //
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
