<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Client;
use App\Product;
use App\Order;
use App\Order_product;
use App\User;

class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-relatorios');
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
        $user_permissions = $this->get_permissions();

        return view('reports', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user()
        ]);
    }

    public function report_delivery() 
    {
        $withdraw = '%';
        if (!empty($_GET['withdraw'])) {
            $withdraw = $_GET['withdraw'];
        }
        
        $produtos = Product::all();
        $por_produto = array();
        foreach ($produtos as $key => $value) {
            $por_produto[$key] = strval($value['id']);
        }
        if (!empty($_GET['por_produto'])) {
            $por_produto = ($_GET['por_produto']) ?? $por_produto;
        }
        
        $orders = array();
        if (!empty($_GET['delivery_date'])) {
            $date = $_GET['delivery_date'];
            
            $orders = Order_product::select('order_products.order_id', 'order_products.delivery_date', 'order_products.product_id', 'order_products.quant', 'orders.withdraw')
            ->join('orders', 'orders.order_number', 'order_products.order_id')
            ->addSelect(DB::raw('sum(order_products.quant) as saldo'))
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(['order_date' => Order::select('order_date')->whereColumn('orders.order_number', 'order_products.order_id')])
            ->addSelect(['client_id' => Order::select('client_id')->whereColumn('orders.order_number', 'order_products.order_id')])
            ->addSelect(['client_name' => Client::select('name')->whereColumn('clients.id', 'client_id')])
            ->addSelect(['client_address' => Client::select('full_address')->whereColumn('id', 'client_id')])
            ->addSelect(['client_phone' => Client::select('contact')->whereColumn('id', 'client_id')])
            ->where('orders.complete_order', 0)
            ->where('orders.withdraw', 'LIKE', $withdraw)
            ->whereIn('order_products.product_id', ($por_produto))
            ->havingRaw('SUM(order_products.quant) <> ?', [0])
            ->orderBy('delivery_date', 'asc')
            ->groupBy('product_id', 'order_id')
            ->get();

            // $total_products = $orders->where('delivery_date', '<=', $date);

            $orders = $orders->where('delivery_date', '<=', $date)
            ->where('delivery_date', '>', '1970-01-01')
            ->where('saldo', '>', 0)
            ->all();
            
        }
        
        $product_total = [];
        foreach ($orders as $key => $value) {
            if (!isset($product_total[$value->product_name])) {
                $product_total[$value->product_name] = [
                    'id' => $value->product_id,
                    'qt' => $value->saldo
                ];
            } else {
                $product_total[$value->product_name] = [
                    'id' => $value->product_id,
                    'qt' => $product_total[$value->product_name]['qt'] + $value->saldo
                ];
            }
        }

        // foreach ($orders as $item) {
        //     $product_quant = DB::table('order_products')
        //     ->select(['id' => Product::select('id')->whereColumn('id', 'product_id')])
        //     ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
        //     ->join('orders', 'orders.order_number', 'order_products.order_id')
        //     ->addSelect(DB::raw('sum(quant) as quant'))
        //     ->where('orders.withdraw', 'LIKE', $withdraw)
        //     ->where('orders.complete_order', '=', 0)
        //     ->where('delivery_date', '<=', $date)
        //     ->whereIn('order_products.product_id', ($por_produto))
        //     ->groupBY('product_id')
        //     ->get();
        // }
        // dd($product_total);
        // $product_total = array();
        // if (!empty($product_quant)) {
        //     foreach ($product_quant as $item) {
        //         $product_total[$item->product_name] = [
        //             'id' => $item->id,
        //             'qt' => $item->quant
        //         ];
        //     }
        // }

        return view('reports_delivery', [
            'orders' => $orders,
            'date' => $date,
            'product_total' => $product_total
        ]);
    }

    public function report_delivery_byPeriod() 
    {
        $withdraw = '%';
        if (!empty($_GET['withdraw'])) {
            $withdraw = $_GET['withdraw'];
        }
        
        $por_produto = Product::get('id');
        if (!empty($_GET['por_produto'])) {
            $por_produto = $_GET['por_produto'] ?? $por_produto;
        }
        $orders = array();
        if (!empty($_GET['date_ini'])) {
            $date_ini = $_GET['date_ini'];
            $date_fin = $_GET['date_fin'];

            $orders = Order_product::select('order_products.order_id', 'order_products.delivery_date', 'product_id', 'quant', 'orders.withdraw')
            ->join('orders', 'orders.order_number', 'order_products.order_id')
            ->addSelect(DB::raw('sum(quant) as saldo'))
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(['order_date' => Order::select('order_date')->whereColumn('orders.order_number', 'order_products.order_id')])
            ->addSelect(['client_id' => Order::select('client_id')->whereColumn('orders.order_number', 'order_products.order_id')])
            ->addSelect(['client_name' => Client::select('name')->whereColumn('clients.id', 'client_id')])
            ->addSelect(['client_address' => Client::select('full_address')->whereColumn('id', 'client_id')])
            ->addSelect(['client_phone' => Client::select('contact')->whereColumn('id', 'client_id')])
            ->where('orders.complete_order', 0)
            ->where('orders.withdraw', 'LIKE', $withdraw)
            ->whereIn('product_id', $por_produto)
            ->havingRaw('SUM(order_products.quant) <> ?', [0])
            ->orderBy('delivery_date', 'asc')
            ->groupBy('product_id', 'order_id', 'client_address')
            ->get();
            
            $orders = $orders->whereBetween('delivery_date', [$date_ini, $date_fin])
            ->all();
        }

        foreach ($orders as $item) {
            $product_quant = DB::table('order_products')
            ->select(['id' => Product::select('id')->whereColumn('id', 'product_id')])
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->join('orders', 'orders.order_number', 'order_products.order_id')
            ->addSelect(DB::raw('sum(quant) as quant'))
            ->where('orders.withdraw', 'LIKE', $withdraw)
            ->where('orders.complete_order', '=', 0)
            ->where('delivery_date', '<=', $date)
            ->whereIn('order_products.product_id', ($por_produto))
            ->groupBY('product_id')
            ->get();
        }

        $product_total = array();
        if (!empty($product_quant)) {
            foreach ($product_quant as $item) {
                $product_total[$item->product_name] = [
                    'id' => $item->id,
                    'qt' => $item->quant
                ];
            }
        }
        
        return view('reports_delivery', [
            'orders' => $orders,
            'date_ini' => $date_ini,
            'date_fin' => $date_fin,
            'product_total' => $product_total
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
