<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Client;
use App\Product;
use App\Order;
use App\Order_product;

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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('reports', ['user' => Auth::user()]);
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
            ->get();
        }
        
        foreach ($orders as $key => $value) {
            $total_product[$value->order_id] = DB::table('order_products')
            ->select(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(DB::raw('sum(quant) as quant_total'))
            ->where('order_id', $value->order_id)
            ->groupBY('product_id')
            ->get();
        }
        
        $product_total = array();
        if (!empty($total_product)) {
            foreach ($total_product as $products) {
                foreach ($products as $item) {
                    if (!isset($product_total[$item->product_name])) {
                        $product_total[$item->product_name] = $item->quant_total;
                    } else {
                        $product_total[$item->product_name] += $item->quant_total;
                    }
                }
            }
        }

        return view('reports_delivery', [
            'orders' => $orders,
            'date' => $date,
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
