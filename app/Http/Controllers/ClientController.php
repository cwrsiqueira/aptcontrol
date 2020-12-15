<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Client;
use App\Product;
use App\Order;
use App\Order_product;
use App\User;
use App\Clients_category;
use Helper;

class ClientController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-clientes');
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
        $clients = Client::orderBy('name')->paginate(10);
        $q = '';
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $clients = Client::where('name', 'LIKE', '%'.$q.'%')->paginate(10);
            $category = Clients_category::where('name', $q)->first();
            if ($category != null) {
                $clients = Client::where('id_categoria', $category['id'])
                ->paginate(10);
            }
        }

        $user_permissions = $this->get_permissions();
        $categories = Clients_category::orderBy('id')->get();
        
        return view('clients', [
            'user' => Auth::user(),
            'clients' => $clients,
            'q' => $q,
            'user_permissions' => $user_permissions,
            'categories' => $categories
        ]);
    }

    public function cc_client($id) 
    {
        $user_permissions = $this->get_permissions();
        if (!in_array('15', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $por_produto = Product::get('id');
        // $date_ini = '2020-01-01';
        // $date_fin = '2020-12-31';
        // if (!empty($_GET['por_produto']) || !empty($_GET['date_ini'])) {
        //     $por_produto = $_GET['por_produto'] ?? $por_produto;
        //     $date_ini = $_GET['date_ini'];
        //     $date_fin = $_GET['date_fin'];
        // }
        if (!empty($_GET['por_produto'])) {
                $por_produto = $_GET['por_produto'] ?? $por_produto;
            }
        $client = Client::find($id);
        $orders = Order::select('order_number')->where('client_id', $id)->get();
        $data = Order_product::whereIn('order_id', $orders)
        ->addSelect(['order_date' => Order::select('order_date')->whereColumn('order_number', 'order_id')])
        ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
        ->addSelect(['orders_order_id' => Order::select('id')->whereColumn('order_number', 'order_id')])
        ->join('orders', 'order_number', 'order_id')
        ->whereIn('product_id', $por_produto)
        // ->whereBetween('delivery_date', [$date_ini, $date_fin])
        ->where('complete_order', 0)
        ->orderBy('delivery_date')
        ->get();

        $data_sum = array();
        foreach($data as $item) {
            $data_sum[] = $item->order_id;
        }

        $saldo = [];
        foreach ($data as $key => $value) {
            if (!isset($saldo[$value->order_id])) {
                $saldo[$value->order_id] = $value->quant;
                $data[$key]['saldo'] = $saldo[$value->order_id];
            } else {
                $saldo[$value->order_id] += $value->quant;
                $data[$key]['saldo'] = $saldo[$value->order_id];
                // if ($saldo[$value->product_id] > $value->quant) {
                //     $data[$key]['saldo'] = $value->quant;
                // } else {
                //     $data[$key]['saldo'] = $saldo[$value->product_id];
                // }
            }
        }

        if(empty($_GET['entregas'])) {
            $data = $data->where('saldo', '>', 0)->where('delivery_date', '>', '0000-00-00');
        }

        foreach ($orders as $key => $value) {
            $total_product[$value->order_number] = DB::table('order_products')
            ->select(['product_id' => Product::select('id')->whereColumn('id', 'product_id')])
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'product_id')])
            ->addSelect(DB::raw('sum(quant) as quant_total'))
            ->where('order_id', $value->order_number)
            ->join('orders', 'order_number', 'order_id')
            ->where('complete_order', 0)
            ->whereIn('order_id', $data_sum)
            ->groupBY('product_id')
            ->get();
        }

        $product_total = array();
        if (!empty($total_product)) {
            foreach ($total_product as $products) {
                foreach ($products as $item) {
                    if (!isset($product_total[$item->product_name])) {
                        $product_total[$item->product_name]['id'] = $item->product_id;
                        $product_total[$item->product_name]['qt'] = $item->quant_total;
                    } else {
                        $product_total[$item->product_name]['qt'] += $item->quant_total;
                    }
                }
            }
        }
        // dd($data);
        return view('cc_client', [
            'data' => $data,
            'client' => $client,
            'product_total' => $product_total,
            'user_permissions' => $user_permissions
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
        $user_permissions = $this->get_permissions();
        if (!in_array('12', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'category',
            'contact',
            'address',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|unique:clients|max:100',
                'category' => 'required',
                'contact' => 'max:50|nullable',
                'address' => 'nullable',
            ]
        )->validate();

        $prod = new Client();
        $prod->name = $data['name'];
        $prod->id_categoria = $data['category'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['address'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Clientes');

        return redirect()->route("clients.index", ['q' => $prod->name]);

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
        $user_permissions = $this->get_permissions();
        if (!in_array('13', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $clients = Client::orderBy('name')->paginate(10);
        $q = '';
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $clients = Client::where('name', 'LIKE', '%'.$q.'%')->paginate(10);
            $category = Clients_category::where('name', $q)->first();
            if ($category != null) {
                $clients = Client::where('id_categoria', $category['id'])
                ->paginate(10);
            }
        }
        
        $client = Client::find($id);
        $user_permissions = $this->get_permissions();
        $categories = Clients_category::orderBy('id')->get();

        return view('clients',[
            'user' => Auth::user(),
            'client' => $client,
            'clients' => $clients,
            'q' => $q,
            'user_permissions' => $user_permissions,
            'categories' => $categories
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
        $user_permissions = $this->get_permissions();
        if (!in_array('13', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'category',
            'contact',
            'address',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'category' => 'required',
                'contact' => 'max:50|nullable',
                'address' => 'nullable',
            ]
        )->validate();

        $prod = Client::find($id);
        $prod->name = $data['name'];
        $prod->id_categoria = $data['category'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['address'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $prod->id, $prod->name, 'Clientes');

        return redirect()->route('clients.index');
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
        if (!in_array('24', $user_permissions) && !Auth::user()->confirmed_user === 1) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $clients = Order::where('client_id', $id)->get();
        if (count($clients) > 0) {
            $message = [
                'cannot_exclude' => 'Cliente não pode ser excluído, pois possui pedidos vinculados!',
            ];
            return redirect()->route('clients.index', ['q' => $_GET['q']])->withErrors($message);
        } else {
            $client = Client::find($id);
            Client::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $client->name, 'Clientes');
            
            return redirect()->route('clients.index', ['q' => $_GET['q']]);
        }
    }
}
