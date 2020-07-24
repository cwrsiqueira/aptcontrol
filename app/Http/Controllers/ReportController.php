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
        $orders = array();
        if (!empty($_GET['delivery_date'])) {
            $date = $_GET['delivery_date'];
            $orders = Order_product::where('delivery_date', $date)->orderBy('order_id')
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(['client_id' => Order::select('client_id')->whereColumn('order_number', 'order_id')])
            ->addSelect(['client_name' => Client::select('name')->whereColumn('id', 'client_id')])
            ->addSelect(['client_address' => Client::select('full_address')->whereColumn('id', 'client_id')])
            ->join('orders', 'order_number', 'order_id')
            ->where('complete_order', 0)
            ->get();
        }
        
        foreach ($orders as $key => $value) {
            $total_product[$value->order_id] = DB::table('order_products')
            ->select(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(DB::raw('sum(quant) as quant_total'))
            ->where('order_id', $value->order_id)
            ->where('delivery_date', $date)
            ->groupBY('product_id')
            ->first();
        }
        
        $product_total = array();
        if (!empty($total_product)) {
            foreach ($total_product as $item) {
                if (!isset($product_total[$item->product_name])) {
                    $product_total[$item->product_name] = $item->quant_total;
                } else {
                    $product_total[$item->product_name] += $item->quant_total;
                }
            }
        }
        
        return view('reports_delivery', [
            'orders' => $orders,
            'date' => $date,
            'product_total' => $product_total
        ]);
    }

    public function report_delivery_byPeriod() 
    {
        $orders = array();
        if (!empty($_GET['date_ini'])) {
            $date_ini = $_GET['date_ini'];
            $date_fin = $_GET['date_fin'];
            $orders = Order_product::whereBetween('delivery_date', [$date_ini, $date_fin])->orderBy('order_id')
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(['client_id' => Order::select('client_id')->whereColumn('order_number', 'order_id')])
            ->addSelect(['client_name' => Client::select('name')->whereColumn('id', 'client_id')])
            ->addSelect(['client_address' => Client::select('full_address')->whereColumn('id', 'client_id')])
            ->join('orders', 'order_number', 'order_id')
            ->where('complete_order', 0)
            ->get();
        }
        
        foreach ($orders as $key => $value) {
            $total_product[$value->order_id] = DB::table('order_products')
            ->select(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(DB::raw('sum(quant) as quant_total'))
            ->where('order_id', $value->order_id)
            ->whereBetween('delivery_date', [$date_ini, $date_fin])
            ->groupBY('product_id')
            ->first();
        }
        
        $product_total = array();
        if (!empty($total_product)) {
            foreach ($total_product as $item) {
                if (!isset($product_total[$item->product_name])) {
                    $product_total[$item->product_name] = $item->quant_total;
                } else {
                    $product_total[$item->product_name] += $item->quant_total;
                }
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
