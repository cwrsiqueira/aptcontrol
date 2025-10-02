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

        return redirect()->route("clients.index", ['q' => $prod->name])->with('success', 'Salvo com sucesso!');
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
            return redirect()->route('clients.index')->withErrors($message);
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

        return redirect()->route('clients.index')->with('success', 'Atualizado com sucesso!');
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
                'cannot_exclude' => 'Cliente possui pedidos vinculados e não pode ser excluído!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        } else {
            $client = Client::find($id);
            Client::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $client->name, 'Clientes');

            return redirect()->route('clients.index')->with('success', 'Excluído com sucesso!');
        }
    }

    public function cc_client(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('clients.cc', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('clients.index')->withErrors($message);
        }

        // Filtro por produto (ids). Padrão: todos.
        $por_produto = $request->input('por_produto');
        if (empty($por_produto)) {
            $por_produto = Product::pluck('id')->all();
        }

        $client = Client::findOrFail($id);
        $complete_order = $request->input('entregas', 0);

        // Linhas dos pedidos em aberto deste cliente, filtradas pelos produtos selecionados
        $data = Order_product::query()
            ->join('orders',   'orders.order_number', '=', 'order_products.order_id')
            ->join('products', 'products.id',         '=', 'order_products.product_id')
            ->where('orders.client_id', $id)
            ->where('orders.complete_order', $complete_order)
            ->whereIn('order_products.product_id', $por_produto)
            ->orderBy('order_products.delivery_date')
            ->select([
                'order_products.*',
                'orders.order_number as order_id',
                'orders.order_date as order_date',
                'orders.id as orders_order_id',
                'products.name as product_name',
            ])
            ->get();

        // Saldo acumulado por pedido (mesma lógica do código original)
        $acc = [];
        foreach ($data as $k => $row) {
            $product = $row->product_id;
            $acc[$product] = ($acc[$product] ?? 0) + $row->quant;
            $data[$k]->saldo = ($acc[$product] > $row->quant) ? $row->quant : $acc[$product];
        }

        // Se NÃO marcar "entregas realizadas", filtra para mostrar só previstas (saldo > 0 e data válida)
        if (!$request->filled('entregas')) {
            $data = $data
                ->where('saldo', '>', 0)
                ->where('delivery_date', '>', '1970-01-01');
        }

        // Pedidos efetivamente presentes após os filtros (para compor os totais por produto)
        $orderNumbersUsados = $data->pluck('order_id')->unique()->values();

        // Totais por produto nos pedidos presentes em $data (mantém comportamento do original)
        $totais = Order_product::query()
            ->join('orders',   'orders.order_number', '=', 'order_products.order_id')
            ->join('products', 'products.id',         '=', 'order_products.product_id')
            ->whereIn('order_products.order_id', $orderNumbersUsados)
            ->where('orders.complete_order', $complete_order)
            ->groupBy('products.id', 'products.name')
            ->select([
                'products.id   as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_products.quant) as quant_total'),
            ])
            ->get();

        // Estrutura esperada pela view: ['Nome do produto' => ['id' => ..., 'qt' => ...]]
        $product_total = [];
        foreach ($totais as $row) {
            $product_total[$row->product_name] = [
                'id' => $row->product_id,
                'qt' => $row->quant_total,
            ];
        }

        return view('cc.cc_client', compact(
            'data',
            'client',
            'product_total',
            'user_permissions',
        ));
    }
}
