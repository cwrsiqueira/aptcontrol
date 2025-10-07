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
            "payment",
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
        $order->payment = $data['payment'];
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

    public function updateStatus(Request $request, Order $order)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $status = $request->input('status', 0);

        $order->update(['complete_order' => $status]);

        Helper::saveLog(Auth::user()->id, 'Reabertura', $order->id, $order->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order->id])->with('success', 'Atualizado com sucesso!');
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
}
