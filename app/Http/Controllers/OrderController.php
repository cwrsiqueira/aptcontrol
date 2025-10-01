<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;
use App\Helpers\Helper;
use App\Seller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('menu-pedidos', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $q = trim((string) $request->input('q'));
        $complete_order = $request->input('complete_order', 0);
        $filter_complete_order = $complete_order == '1' ? [1, 2] : [0];

        $orders = Order::query()
            ->select([
                'orders.*',
            ])
            ->join('clients', 'clients.id', '=', 'orders.client_id')      // mantém inner se cliente é obrigatório
            ->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id')  // <-- permite seller_id NULL
            ->when($q, function ($qb) use ($q) {
                $needle = mb_strtolower(Str::ascii($q));
                $qb->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(unaccent(clients.name)) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(unaccent(sellers.name)) LIKE ?', ["%{$needle}%"])
                        ->orWhere('orders.order_number', 'LIKE', "%{$needle}%");
                });
            })
            ->whereIn('complete_order', $filter_complete_order)
            ->orderBy('orders.order_date')
            ->paginate(10)
            ->withQueryString();

        return view('orders.orders', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'orders' => $orders,
            'q' => $q,
            'complete_order' => $complete_order,
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
        if (!in_array('orders.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }
        $seq_order_number = $this->get_seq_order_number();

        $clients = Client::orderBy('name')->get(['id', 'name']);
        $sellers = Seller::orderBy('name')->get(['id', 'name']);

        return view('orders.orders_create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'seq_order_number' => $seq_order_number,
            'clients' => $clients,
            'sellers' => $sellers,
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
        if (!in_array('orders.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $data = $request->only([
            "order_date",
            "client_name",
            "order_number",
            "withdraw",
            "seller_name",
        ]);

        Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "client_name" => ['required'],
                "order_number" => ['required', 'unique:orders'],
                'withdraw' => ['required'],
                'seller_name' => ['required'],
            ],
            [],
            [
                'client_name' => 'Cliente',
                'seller_name' => 'Vendedor',
            ]
        )->validate();

        $client = Client::firstOrCreate(['name' => trim($data['client_name'])], ['id_categoria' => 1]);
        $seller = Seller::firstOrCreate(['name' => trim($data['seller_name'])], ['contact_type' => 'outro']);

        $order = new Order();
        $order->client_id = $client->id;
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->payment = 'Aberto';
        $order->withdraw = $data['withdraw'];
        $order->seller_id = $seller->id;
        $order->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order->id, $order->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order->id])->with('success', 'Salvo com sucesso!');
    }

    public function show(Order $order)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order->load(['client', 'seller']);
        $order_products = Order_product::where('order_id', $order->order_number)
            ->orderBy('delivery_date')
            ->get();

        return view('orders.orders_view', compact(
            'order',
            'order_products',
            'user_permissions',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $clients = Client::orderBy('name')->get(['id', 'name']);
        $sellers = Seller::orderBy('name')->get(['id', 'name']);

        return view('orders.orders_edit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'order' => $order,
            'clients' => $clients,
            'sellers' => $sellers,
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
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $data = $request->only([
            "order_date",
            "client_name",
            "order_number",
            "seller_name",
            "withdraw",
        ]);

        Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "client_name" => ['required'],
                "order_number" => ['required', Rule::unique('orders', 'order_number')->ignore($id)],
                'seller_name' => ['required'],
                'withdraw' => ['required'],
            ],
            [],
            [
                'client_name' => 'Cliente',
                'seller_name' => 'Vendedor'
            ]
        )->validate();

        $client = Client::firstOrCreate(['name' => trim($data['client_name'])], ['id_categoria' => 1]);
        $seller = Seller::firstOrCreate(['name' => trim($data['seller_name'])], ['contact_type' => 'outro']);

        $order = Order::find($id);
        $order->client_id = $client->id;
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->withdraw = $data['withdraw'];
        $order->seller_id = $seller->id;
        $order->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $order->id, $order->order_number, 'Pedidos');

        return redirect()->route('orders.index', ['q' => $order->order_number])->with('success', 'Atualizado com sucesso!');
    }

    public function destroy(Order $order)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.delete', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $orders = Order_product::where('order_id', $order->order_number)->get();

        if (count($orders) > 0) {
            $message = [
                'cannot_exclude' => 'Pedido possui produtos vinculados e não pode ser excluído!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        } else {
            $order = Order::find($order->id);
            $order->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $order->id, $order->order_number, 'Pedidos');
            return redirect()->route('orders.index')->with('success', 'Excluído com sucesso!');
        }
    }

    public function cc_order(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.cc', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order  = Order::findOrFail($id);
        $client = Client::findOrFail($order->client_id);

        // Filtro por produto (ids). Se vazio, não aplica whereIn (equivalente a "todos").
        $por_produto = (array) $request->input('por_produto', []);
        $complete_order = $request->input('entregas', 0);

        // Linhas do pedido em aberto, opcionalmente filtradas pelos produtos selecionados
        $data = Order_product::query()
            ->join('orders',   'orders.order_number',  '=', 'order_products.order_id')
            ->join('products', 'products.id',          '=', 'order_products.product_id')
            ->where('order_products.order_id', $order->order_number)
            ->where('orders.complete_order', $complete_order)
            ->when(!empty($por_produto), fn($q) => $q->whereIn('order_products.product_id', $por_produto))
            ->orderBy('order_products.delivery_date')
            ->select([
                'order_products.*',
                'orders.order_number as order_id',
                'orders.order_date   as order_date',
                'orders.id           as orders_order_id',
                'products.name       as product_name',
            ])
            ->get();

        // Saldo acumulado por pedido (mesma lógica do original)
        $acc = [];
        foreach ($data as $k => $row) {
            $product = $row->product_id;
            $acc[$product] = ($acc[$product] ?? 0) + $row->quant;
            $data[$k]->saldo = ($acc[$product] > $row->quant) ? $row->quant : $acc[$product];
        }

        // Se NÃO marcar "entregas realizadas", mostra somente previstas
        if (!$request->filled('entregas')) {
            $data = $data
                ->where('saldo', '>', 0)
                ->where('delivery_date', '>', '1970-01-01');
        }

        // Totais por produto para montar os checkboxes (sempre do pedido inteiro, independente do filtro por produto)
        $totais = Order_product::query()
            ->join('orders',   'orders.order_number',  '=', 'order_products.order_id')
            ->join('products', 'products.id',          '=', 'order_products.product_id')
            ->where('order_products.order_id', $order->order_number)
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

        return view('cc.cc_order', compact(
            'data',
            'client',
            'product_total',
            'user_permissions',
            'order'
        ));
    }

    private function get_seq_order_number()
    {
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

        $seq_order_number = 'sn-' . ($seq + 1);

        return $seq_order_number;
    }

    public function toggleDateFavorite($orderId)
    {
        $user_permissions = Helper::get_permissions();
        // Mantendo mesma permissão '10' da tela C/C Produto
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order = \App\Order::findOrFail($orderId);
        $order->favorite_date = !$order->favorite_date;
        $order->save();

        return response()->json(['ok' => true, 'favorite_date' => (bool) $order->favorite_date]);
    }

    public function toggleDeliveryFavorite($orderProductId)
    {
        $user_permissions = Helper::get_permissions();
        // mesma permissão que você já usa na tela C/C Produto
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $op = Order_product::findOrFail($orderProductId);
        $op->favorite_delivery = !$op->favorite_delivery;
        $op->save();

        return response()->json(['ok' => true, 'favorite_delivery' => (bool) $op->favorite_delivery, 'id' => $orderProductId]);
    }
}
