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
    public function index()
    {
        if (isset($_GET['comp']) && $_GET['comp'] == 1) {
            $comps = array(1, 2);
            $comp = 1;
        } else {
            $comps = array(0);
            $comp = 0;
        }

        $sellerId = 0;
        if (!empty($_GET['seller_id']) && is_numeric($_GET['seller_id'])) {
            $sellerId = (int) $_GET['seller_id'];
        }

        $orders = Order::addSelect([
            'name_client' => Client::select('name')->whereColumn('clients.id', 'orders.client_id'),
            'name_seller' => Seller::select('name')->whereColumn('sellers.id', 'orders.seller_id'),
        ])
            ->whereIn('complete_order', $comps)
            ->when($sellerId > 0, function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->orderBy('order_date', 'asc')
            ->paginate(10);

        if (!empty($_GET['q'])) {

            $q = \DateTime::createFromFormat('d/m/Y', $_GET['q']);
            if ($q && $q->format('d/m/Y') === $_GET['q']) {
                $q = $_GET['q'];
                $q = explode('/', $q);
                $q = array_reverse($q);
                $q = implode('-', $q);
                // A consulta É por data
                $orders = Order::whereIn('complete_order', $comps)
                    ->where('order_date', $q)
                    ->addSelect(['name_client' => Client::select('name')
                        ->whereColumn('clients.id', 'orders.client_id')])
                    ->orderBy('id', 'desc')
                    ->paginate(10);

                $q = date('d/m/Y', strtotime($q));
            } else {
                $q = $_GET['q'];
                // A consulta NÃO é por data

                $orders = Order::where('order_number', 'LIKE', '%' . $q . '%')
                    ->whereIn('complete_order', $comps)
                    ->when($sellerId > 0, function ($q2) use ($sellerId) {
                        $q2->where('seller_id', $sellerId);
                    })
                    ->addSelect([
                        'name_client' => Client::select('name')->whereColumn('clients.id', 'orders.client_id'),
                        'name_seller' => Seller::select('name')->whereColumn('sellers.id', 'orders.seller_id'),
                    ])
                    ->orderBy('id', 'desc')
                    ->paginate(10);
            }
        } else {
            $q = '';
        }

        $user_permissions = Helper::get_permissions();

        $get_orders_repeated = Order::select('order_number')
            ->addSelect(DB::raw('count(*) as contador'))
            ->groupBy('order_number')
            ->havingRaw('count(*) > ?', [1])
            ->get();

        $orders_repeated = array();
        foreach ($get_orders_repeated as $item) {
            $orders_repeated[$item->order_number] = Order::where('order_number', $item->order_number)->get();
        }

        $sellers = Seller::orderBy('name')->get(['id', 'name']);

        return view('orders.orders', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'orders' => $orders,
            'q' => $q,
            'comp' => $comp,
            'orders_repeated' => $orders_repeated,
            'sellers' => $sellers,
            'sellerId' => $sellerId,
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
            "seller_name",
        ]);

        Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "client_name" => ['required'],
                "order_number" => ['required', 'unique:orders'],
                'seller_name' => ['required'],
            ],
            [],
            [
                'client_name' => 'Cliente',
                'seller_name' => 'Vendedor'
            ]
        )->validate();

        $client = Client::firstOrCreate(['name' => trim($data['client_name'])], ['id_categoria' => 1]);
        $seller = Seller::firstOrCreate(['name' => trim($data['seller_name'])], ['contact_type' => 'outro']);

        $order = new Order();
        $order->client_id = $client->id;
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->payment = 'Aberto';
        $order->withdraw = 'entregar';
        $order->seller_id = $seller->id;
        $order->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order->id, $order->order_number, 'Pedidos');

        return redirect()->route('order_products.index', ['order' => $order->id]);
    }

    public function show($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order = Order::addSelect(['name_client' => Client::select('name')
            ->whereColumn('id', 'orders.client_id')])
            ->find($id);

        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'order_products.product_id')])
            ->addSelect(['product_id' => Product::select('id')->whereColumn('id', 'order_products.product_id')])
            ->addSelect(DB::raw("sum(order_products.quant) as saldo"))
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->orderBy('delivery_date')
            ->get();

        $order_products = Order_product::where('order_id', $order->order_number)
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'order_products.product_id')])
            // ->orderBy('product_id')
            ->orderBy('delivery_date')
            ->get();

        $user_permissions = Helper::get_permissions();
        $product = array();
        $products = json_decode($saldo_produtos);
        foreach ($products as $item) {
            if ($item->saldo != 0) {
                $product[$item->product_id] = $item->product_name;
            }
        }

        return view('orders.orders_view', [
            'order' => $order,
            'order_products' => $order_products,
            'user_permissions' => $user_permissions,
            'product' => $product,
            'saldo_produtos' => $saldo_produtos
        ]);
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

        return redirect()->route('orders.index', ['q' => $order->order_number]);
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

        $orders = Order_product::where('order_id', $order->id)->get();

        if (count($orders) > 0) {
            $message = [
                'cannot_exclude' => 'Pedido possui produtos vinculados e não pode ser excluído!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        } else {
            $order = Order::find($order->id);
            $order->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $order->id, $order->order_number, 'Pedidos');
            return redirect()->route('orders.index');
        }
    }

    public function cc_order(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.cc', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()
                ->route('orders.index')
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $order = Order::findOrFail($id);
        $client = Client::findOrFail($order->client_id);

        // Filtro por produto (ids) – padrão: todos
        $por_produto = $request->input('por_produto');
        if (empty($por_produto)) {
            $por_produto = Product::pluck('id')->all();
        }

        // Itens dos pedidos em aberto, filtrados pelos produtos selecionados
        $data = Order_product::query()
            ->join('orders',   'orders.order_number',  '=', 'order_products.order_id')
            ->join('products', 'products.id',          '=', 'order_products.product_id')
            ->whereIn('order_products.product_id', $por_produto)
            ->where('order_products.order_id',  $order->order_number)
            ->where('orders.complete_order', 0)
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
        $saldoPorPedido = [];
        foreach ($data as $k => $row) {
            $pedido = $row->order_id;
            $saldoPorPedido[$pedido] = ($saldoPorPedido[$pedido] ?? 0) + $row->quant;
            $data[$k]->saldo = $saldoPorPedido[$pedido];
        }

        // Filtro de entregas (mantém a mesma regra baseada na presença de "entregas")
        if (!$request->filled('entregas')) {
            $data = $data
                ->where('saldo', '>', 0)
                ->where('delivery_date', '>', '1970-01-01');
        }

        // Pedidos efetivamente presentes após os filtros
        $orderNumbersUsados = $data->pluck('order_id')->unique()->values();

        // Totais por produto considerando os pedidos presentes em $data
        // (mantém o comportamento original: não re-filtra pelos produtos selecionados aqui)
        $totais = Order_product::query()
            ->join('orders',   'orders.order_number',  '=', 'order_products.order_id')
            ->join('products', 'products.id',          '=', 'order_products.product_id')
            ->whereIn('order_products.order_id', $orderNumbersUsados)
            ->where('orders.complete_order', 0)
            ->groupBy('products.id', 'products.name')
            ->select([
                'products.id   as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_products.quant) as quant_total'),
            ])
            ->get();

        // Estrutura igual à usada pela view: ['Nome do produto' => ['id' => ..., 'qt' => ...]]
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function orders_conclude()
    {
        if (!empty($_GET['order'])) {
            $id = $_GET['order'];
        }

        $user_permissions = Helper::get_permissions();
        if (!in_array('orders.conclude', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order = Order::addSelect([
            'name_client' => Client::select('name')->whereColumn('id', 'orders.client_id'),
            'seller_name' => Seller::select('name')->whereColumn('sellers.id', 'orders.seller_id')
        ])
            ->find($id);

        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'order_products.product_id')])
            ->addSelect(['product_id' => Product::select('id')->whereColumn('id', 'order_products.product_id')])
            ->addSelect(DB::raw("sum(order_products.quant) as saldo"))
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->orderBy('delivery_date')
            ->get();

        $order_products = Order_product::where('order_id', $order->order_number)
            ->addSelect(['product_name' => Product::select('name')->whereColumn('id', 'order_products.product_id')])
            ->orderBy('product_id')
            ->orderBy('delivery_date')
            ->get();

        $user_permissions = Helper::get_permissions();
        $product = array();
        $products = json_decode($saldo_produtos);
        foreach ($products as $item) {
            if ($item->saldo != 0) {
                $product[$item->product_id] = $item->product_name;
            }
        }

        return view('orders.orders_conclude', [
            'order' => $order,
            'order_products' => $order_products,
            'user_permissions' => $user_permissions,
            'product' => $product,
            'saldo_produtos' => $saldo_produtos
        ]);
    }

    public function toggleDateFavorite($orderId)
    {
        $user_permissions = Helper::get_permissions();
        // Mantendo mesma permissão '10' da tela C/C Produto
        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'msg' => 'Sem permissão.'], 403);
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
        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'msg' => 'Sem permissão.'], 403);
        }

        $op = Order_product::findOrFail($orderProductId);
        $op->favorite_delivery = !$op->favorite_delivery;
        $op->save();

        return response()->json(['ok' => true, 'favorite_delivery' => (bool) $op->favorite_delivery, 'id' => $orderProductId]);
    }

    public function add_line(Request $request)
    {
        $data = $request->only([
            'id_order',
            "order_id",
            "product_id",
            "quant",
            "delivery_date",
            "fixar_data",
        ]);

        Validator::make(
            $data,
            [
                "id_order" => ['required'],
                "order_id" => ['required', 'string'],
                "product_id" => ['required'],
                "quant" => ['required'],
                "delivery_date" => ['required', 'date'],
            ]
        )->validate();

        $data['quant'] = str_replace('.', '', $data['quant']);

        $add_line = new Order_product();
        $add_line->order_id = $data['order_id'];
        $add_line->product_id = $data['product_id'];
        $add_line->quant = $data['quant'];
        $add_line->unit_price = 0;
        $add_line->total_price = 0;
        $add_line->delivery_date = $data['delivery_date'];
        $add_line->favorite_delivery = isset($data['fixar_data']) ? 1 : 0;
        $add_line->save();

        $total_order = Order::where('order_number', $add_line->order_id)->first();
        $total_order->order_total = 0;
        $total_order->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $add_line->id, $add_line->order_id, 'Pedidos');

        return redirect()->route('orders.edit', ['order' => $data['id_order']]);
    }

    /**
     * Atualiza uma linha (item) de um pedido e recalcula o total da ordem.
     *
     * Este método:
     * 1. Valida os dados enviados do formulário.
     * 2. Converte os valores numéricos do formato brasileiro (1.234,56) para decimal.
     * 3. Atualiza as informações de um item específico na tabela `order_products`.
     * 4. Recalcula o valor total da ordem, somando todos os `total_price` de
     *    `order_products` vinculados a esta ordem (`order_products.order_id = orders.order_number`).
     * 5. Atualiza o campo `order_total` na tabela `orders` com o valor recalculado.
     * 6. Registra a alteração no log do sistema.
     *
     * @param  \Illuminate\Http\Request  $request  Dados da requisição contendo informações do item do pedido.
     * @return \Illuminate\Http\RedirectResponse   Redireciona de volta para a tela de edição do pedido.
     */
    public function edit_line(Request $request)
    {
        if (!Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Acesso permitido somente para administradores!',
            ];
            return redirect()->route('orders.index')->with('error', 'Acesso permitido somente para administradores!');
        }

        // Coleta somente os campos necessários vindos do formulário
        $data = $request->only([
            'id',
            'order_id',     // número da ordem (orders.order_number)
            'id_order',     // id do item (order_products.id)
            'product_id',   // produto selecionado
            'quant',        // quantidade
            'delivery_date', // data de entrega
            'edit_fixar_data',
        ]);

        /**
         * Validação dos campos obrigatórios
         */
        Validator::make($data, [
            'id'            => 'required',
            'order_id'      => 'required',
            'id_order'      => 'required',
            'product_id'    => 'required',
            'quant'         => 'required',
            'delivery_date' => 'required',
        ])->validate();

        /**
         * Converte valores do formato brasileiro (1.234,56) para decimal
         * - Primeiro remove os pontos dos milhares
         * - Depois troca vírgula decimal por ponto
         */
        $data['quant']       = (float) str_replace(',', '.', str_replace('.', '', $data['quant']));

        /**
         * DB::transaction -> garante que todas as operações serão executadas juntas
         * - Se algum passo falhar, nada será salvo (rollback)
         */
        DB::transaction(function () use ($data) {
            /**
             * 1) Atualiza a linha (item) da ordem na tabela order_products
             */
            $edit_line = Order_product::findOrFail($data['id']);
            $edit_line->product_id    = $data['product_id'];
            $edit_line->quant         = $data['quant'];
            $edit_line->unit_price    = 0;
            $edit_line->total_price   = 0;
            $edit_line->delivery_date = $data['delivery_date'];
            $edit_line->favorite_delivery = isset($data['edit_fixar_data']) ? 1 : 0;
            $edit_line->save();

            /**
             * 4) Grava log da alteração (usuário, ação, id, etc.)
             */
            Helper::saveLog(Auth::user()->id, 'Alteração', $edit_line->id, $edit_line->order_id, 'Pedidos');
        });

        /**
         * Redireciona de volta para a tela de edição da ordem
         */
        return redirect()->route('orders.edit', ['order' => $data['id_order']]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function order_product_destroy(Order_product $order_product)
    {
        $id = $order_product->id;
        $order_product = Order_product::find($id);
        $order_number = $order_product->order_id;
        $order = Order::where('order_number', $order_number)->first();
        if ($order_product->quant > 0) {
            $order->order_total = $order->order_total - $order_product->total_price;
            $order->save();
        }
        $order_product->delete();
        Helper::saveLog(Auth::user()->id, 'Alteração', $id, $order->id, 'Pedidos');
        return redirect()->route('orders.edit', ['order' => $order->id]);
    }
}
