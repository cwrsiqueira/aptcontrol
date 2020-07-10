<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\Order;
use App\Client;
use App\Order_product;
use App\Stockmovement;

class ProductController extends Controller
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
        $products = Product::paginate(5);
        $q = '';
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $products = Product::where('name', 'LIKE', '%'.$q.'%')->paginate(5);
        }
        
        return view('products', [
            'user' => Auth::user(),
            'products' => $products,
            'q' => $q
        ]);
    }

    public function cc_product($id) 
    {
        $product = Product::find($id);
        $data = Order_product::where('product_id', $id)
            ->addSelect(['order_date' => Order::select('order_date')->whereColumn('order_number', 'order_id')])
            ->addSelect(['client_id' => Order::select('client_id')->whereColumn('order_number', 'order_id')])
            ->addSelect(['client_name' => Client::select('name')->whereColumn('id', 'client_id')])
            ->orderBy('delivery_date')
            ->paginate(10);
        $quant_total = Order_product::select(DB::raw("(select sum(quant) from order_products where order_products.product_id = '$id') as quant_total"))->first();

        if (!empty($quant_total)) {
            $days_necessary = ((intval($quant_total->quant_total)) - $product->current_stock) / $product->daily_production_forecast;
            
            if ($days_necessary <= 0) {
                $days_necessary = 0;
            }
            $delivery_in = date('Y-m-d', strtotime(date('Y-m-d').' +'.(ceil($days_necessary)+1).' days'));
        } else {
            $delivery_in = date('Y-m-d');
        }

        return view('cc_product', [
            'data' => $data,
            'product' => $product,
            'quant_total' => $quant_total,
            'delivery_in' => $delivery_in
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
        $data = $request->only([
            'name',
            'stock',
            'forecast',
            'file',
        ]);
        
        if (!empty($data['stock'])) {
            $data['stock'] = str_replace('.', '', $data['stock']);
        }
        if (!empty($data['forecast'])) {
            $data['forecast'] = str_replace('.', '', $data['forecast']);
        }

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|unique:products|max:100',
                'stock' => 'integer|nullable',
                'forecast' => 'integer|required',
                'file' => 'image|mimes:jpeg,jpg,png|nullable'
            ]
        )->validate();

        if (!empty($data['file'])) {
            $data['file'] = 'preenchido';
        } else {
            $data['file'] = 'não informado';
        }

        $prod = new Product();
        $prod->name = $data['name'];
        $prod->current_stock = $data['stock'];
        $prod->daily_production_forecast = $data['forecast'];
        $prod->img_url = $data['file'];
        $prod->save();

        return redirect()->route('products.index');

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
        $products = Product::paginate(5);
        $product = Product::find($id);
        if (!empty($_GET['action'])) {
            $action = $_GET['action'];
        }
        return view('products',[
            'user' => Auth::user(),
            'product' => $product,
            'products' => $products,
            'action' => $action
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
        if (!empty($request->input('add_stock'))) {
            $data = $request->only([
                'name',
                'add_stock',
                'dt_add_estoque',
            ]);
    
            $data['add_stock'] = str_replace('.', '', $data['add_stock']);
    
            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|max:100',
                    'add_stock' => 'integer|nullable',
                    'dt_add_estoque' => 'date|nullable'
                ]
            )->validate();
    
            $prod = Product::find($id);
            $prod->current_stock = $prod->current_stock + $data['add_stock'];
            $prod->save();

            $mov_stock = new Stockmovement();
            $mov_stock->product_id = $id;
            $mov_stock->movement_date = $data['dt_add_estoque'];
            $mov_stock->movement_quant = $data['add_stock'];
            $mov_stock->save();
        }

        if (!empty($request->input('forecast'))) {
            $data = $request->only([
                'name',
                'stock',
                'forecast',
                'file',
            ]);
    
            $data['stock'] = str_replace('.', '', $data['stock']);
            $data['forecast'] = str_replace('.', '', $data['forecast']);
    
            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|max:100',
                    'stock' => 'integer|nullable',
                    'forecast' => 'integer|required',
                    'file' => 'image|mimes:jpeg,jpg,png|nullable',
                ]
            )->validate();
    
            if (!empty($data['file'])) {
                $data['file'] = 'preenchido';
            } else {
                $data['file'] = 'não informado';
            }
    
            $prod = Product::find($id);
            $prod->name = $data['name'];
            $prod->current_stock = $data['stock'];
            $prod->daily_production_forecast = $data['forecast'];
            $prod->img_url = $data['file'];
            $prod->save();
        }

        return redirect()->route('products.index');
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
