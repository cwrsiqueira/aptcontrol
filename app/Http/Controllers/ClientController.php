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
use App\Clients_category;
use App\Helpers\Helper;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('menu-clientes', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $q = trim((string) $request->input('q'));

        $clients = Client::query()
            ->select('clients.*', 'clients_categories.name as category_name')
            ->join('clients_categories', 'clients_categories.id', 'clients.id_categoria')
            ->when($q, function ($qb) use ($q) {
                $needle = mb_strtolower(Str::ascii($q));
                $qb->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(unaccent(clients.name)) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(unaccent(clients_categories.name)) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->orderBy('clients.name')
            ->paginate(10)
            ->withQueryString();

        return view('clients.clients', [
            'user' => Auth::user(),
            'clients' => $clients,
            'q' => $q,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function cc_client($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.cc', $user_permissions) && !Auth::user()->is_admin) {
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
        foreach ($data as $item) {
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

        if (empty($_GET['entregas'])) {
            $data = $data->where('saldo', '>', 0)->where('delivery_date', '>', '1970-01-01');
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

        return view('cc.cc_client', [
            'data' => $data,
            'client' => $client,
            'product_total' => $product_total,
            'user_permissions' => $user_permissions
        ]);
    }

    public function toggleFavorite($clientId)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'msg' => 'Sem permissão.'], 403);
        }

        $client = \App\Client::findOrFail($clientId);
        $client->is_favorite = !$client->is_favorite;
        $client->save();

        return response()->json(['ok' => true, 'is_favorite' => (bool) $client->is_favorite]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('clients.index')->withErrors($message);
        }

        return view('clients.clients_create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'categories' => Clients_category::all(),
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
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'id_category',
            'contact',
            'full_address',
        ]);

        Validator::make(
            $data,
            [
                'name' => 'required|unique:clients|max:100',
                'id_category' => 'required',
                'contact' => 'max:50|nullable',
                'full_address' => 'nullable',
            ]
        )->validate();

        $prod = new Client();
        $prod->name = $data['name'];
        $prod->id_categoria = $data['id_category'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['full_address'];
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
    public function show(Client $client)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        return view('clients.clients_view', [
            'user'             => Auth::user(),
            'client'           => $client,
            'user_permissions' => $user_permissions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }
        if (!in_array('clients.cc', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        return view('clients.clients_edit', [
            'user' => Auth::user(),
            'client' => $client,
            'user_permissions' => $user_permissions,
            'categories' => Clients_category::all(),
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
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'id_category',
            'contact',
            'full_address',
        ]);

        Validator::make(
            $data,
            [
                'name' => [
                    'required',
                    'max:100',
                    Rule::unique('clients', 'name')->ignore($id), // ignora o próprio registro
                ],
                'id_category' => 'required',
                'contact' => 'max:50|nullable',
                'full_address' => 'nullable',
            ]
        )->validate();

        $prod = Client::find($id);
        $prod->name = $data['name'];
        $prod->id_categoria = $data['id_category'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['full_address'];
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
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.delete', $user_permissions) && !Auth::user()->is_admin) {
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
            return redirect()->route('clients.index')->withErrors($message);
        } else {
            $client = Client::find($id);
            Client::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $client->name, 'Clientes');

            return redirect()->route('clients.index');
        }
    }
}
