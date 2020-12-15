<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Product;
use App\Order;
use App\Client;
use App\Order_product;
use App\Stockmovement;
use App\User;
use App\Clients_category;
use Helper;

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
        $this->middleware('can:menu-produtos');
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
        $products = Product::paginate(5);
        $q = '';
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $products = Product::where('name', 'LIKE', '%'.$q.'%')->paginate(5);
        }

        $user_permissions = $this->get_permissions();
        
        return view('products', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'products' => $products,
            'q' => $q
        ]);
    }

    public function cc_product($id) 
    {

        $user_permissions = $this->get_permissions();
        if (!in_array('10', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        }

        $categories = Clients_category::orderBy('id')->get();
        $cats = array(999 => 0);
        foreach ($categories as $key => $value) {
            $cats[$key] = $value['id'];
        }
        if (!empty($_GET['por_categoria'])) {
            $cats = $_GET['por_categoria'];
        }
        
        $product = Product::find($id);
        $data = Order_product::select('*', 'quant as saldo')
        ->join('orders', 'orders.order_number', 'order_products.order_id')
        ->join('clients', 'clients.id', 'client_id')
        ->addSelect(['order_date' => Order::select('order_date')->whereColumn('orders.order_number', 'order_products.order_id')])
        ->addSelect(['client_id' => Order::select('client_id')->whereColumn('orders.order_number', 'order_products.order_id')])
        ->addSelect(['client_name' => Client::select('name')->whereColumn('clients.id', 'client_id')])
        ->addSelect(['client_id_categoria' => Client::select('id_categoria')->whereColumn('clients.id', 'client_id')])
        ->addSelect(['category_name' => Clients_category::select('name')->whereColumn('clients_categories.id', 'client_id_categoria')])
        ->where('product_id', $id)
        ->where('orders.complete_order', 0)
        // ->where('delivery_date', '>', '0000-00-00')
        ->whereIn('clients.id_categoria', $cats)
        ->orderBy('delivery_date')
        ->get();
        
        $saldo = [];
        foreach ($data as $key => $value) {
            if (!isset($saldo[$value->order_id])) {
                $saldo[$value->order_id] = $value->quant;
                $data[$key]['saldo'] = $saldo[$value->order_id];
            } else {
                $saldo[$value->order_id] += $value->quant;
                if ($saldo[$value->order_id] > $value->quant) {
                    $data[$key]['saldo'] = $value->quant;
                } else {
                    $data[$key]['saldo'] = $saldo[$value->order_id];
                }
            }
        }
        
        $data = $data->where('saldo', '>', 0)->where('delivery_date', '>', '0000-00-00');
        
        // $data = Order_product::select('*')
        // ->join('orders', 'orders.order_number', 'order_products.order_id')
        // ->join('clients', 'clients.id', 'client_id')
        // ->addSelect(DB::raw('sum(order_products.quant) as saldo'))
        // ->addSelect(['order_date' => Order::select('order_date')->whereColumn('orders.order_number', 'order_products.order_id')])
        // ->addSelect(['client_id' => Order::select('client_id')->whereColumn('orders.order_number', 'order_products.order_id')])
        // ->addSelect(['client_name' => Client::select('name')->whereColumn('clients.id', 'client_id')])
        // ->addSelect(['client_id_categoria' => Client::select('id_categoria')->whereColumn('clients.id', 'client_id')])
        // ->addSelect(['category_name' => Clients_category::select('name')->whereColumn('clients_categories.id', 'client_id_categoria')])
        // ->where('product_id', $id)
        // ->where('orders.complete_order', 0)
        // ->whereIn('clients.id_categoria', $cats)
        // ->groupBy('order_products.order_id')
        // ->havingRaw('SUM(order_products.quant) <> ?', [0])
        // ->orderBy('delivery_date')
        // ->get();

        $quant_por_categoria = Order_product::join('orders', 'orders.order_number', 'order_products.order_id')
        ->join('clients', 'clients.id', 'orders.client_id')
        ->join('clients_categories', 'clients_categories.id', 'clients.id_categoria')
        ->addSelect(DB::raw('sum(order_products.quant) as saldo'))
        ->addSelect(['name' => Clients_category::select('name')->whereColumn('clients_categories.id', 'clients.id_categoria')])
        ->addSelect(['id' => Clients_category::select('id')->whereColumn('clients_categories.id', 'clients.id_categoria')])
        ->where('product_id', $id)
        ->where('orders.complete_order', 0)
        ->groupBy('clients.id_categoria')
        ->get();

        $day_delivery_calc = $this->day_delivery_calc($id);
        $quant_total = $day_delivery_calc['quant_total'];
        $delivery_in = $day_delivery_calc['delivery_in'];
        
        return view('cc_product', [
            'data' => $data,
            'product' => $product,
            'quant_total' => $quant_total,
            'delivery_in' => $delivery_in,
            'user_permissions' => $user_permissions,
            'categories' => $categories,
            'quant_por_categoria' => $quant_por_categoria
        ]);
    }

    public function day_delivery_recalc($id_product)
    {
        Helper::day_delivery_recalc($id_product);
        return redirect()->route('cc_product', ['id' => $id_product]);

    }

    private function day_delivery_calc($id) {
        $product = Product::find($id);
        $quant_total = Order_product::select('*')
        ->join('orders', 'order_number', 'order_id')
        ->where('order_products.product_id', $id)
        ->where('orders.complete_order', 0)
        ->sum('quant');
        
        if (!empty($quant_total)) {
            $days_necessary = ((intval($quant_total)) - $product->current_stock) / $product->daily_production_forecast;
            
            if ($days_necessary <= 0) {
                $days_necessary = 0;
            }
            $delivery_in = date('Y-m-d', strtotime(date('Y-m-d').' +'.(ceil($days_necessary)).' days'));
        } else {
            $delivery_in = date('Y-m-d', strtotime(date('Y-m-d').' +1 days'));
        }

        return array(
            'quant_total' => $quant_total,
            'delivery_in' => $delivery_in
        );
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

        $user_permissions = $this->get_permissions();
        if (in_array('7', $user_permissions) || Auth::user()->confirmed_user === 1) {
            $data['auth'] = 'Autorizado';
        }
        
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
                'file' => 'image|mimes:jpeg,jpg,png|nullable',
                'auth' => 'required'
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

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Produtos');

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
        
        $user_permissions = $this->get_permissions();
        if ($action == 'edit') {
            if (!in_array('8', $user_permissions) && !Auth::user()->confirmed_user === 1) {
                $action = 'Não Autorizado';
            }
        }  elseif ($action == 'add_estock') {
            if (!in_array('9', $user_permissions) && !Auth::user()->confirmed_user === 1) {
                $action = 'Não Autorizado';
            }
        }

        return view('products',[
            'user' => Auth::user(),
            'product' => $product,
            'products' => $products,
            'action' => $action,
            'user_permissions' => $user_permissions
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

        Helper::saveLog(Auth::user()->id, 'Alteração', $prod->id, $prod->name, 'Produtos');

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
        $user_permissions = $this->get_permissions();
        if (!in_array('11', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        }

        $products = Order_product::where('product_id', $id)->get();
        if (count($products) > 0) {
            $message = [
                'cannot_exclude' => 'Produto não pode ser excluído, pois possui pedidos vinculados!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        } else {
            $product = Product::find($id);
            Product::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $product->name, 'Produtos');
            return redirect()->route('products.index');
        }
    }
}
