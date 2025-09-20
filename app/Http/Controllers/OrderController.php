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

        $orders = Order::addSelect(
            ['name_client' => Client::select('name')->whereColumn('id', 'orders.client_id')]
        )
            ->whereIn('complete_order', $comps)
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
                    ->addSelect(
                        ['name_client' => Client::select('name')->whereColumn('clients.id', 'orders.client_id')]
                    )
                    ->orderBy('id', 'desc')
                    ->paginate(10);
            }
        } else {
            $q = '';
        }

        // if (!empty($_GET['q'])) {

        //     $q = $_GET['q'];

        //     $orders = Order::where('order_number', 'LIKE', '%'.$q.'%')
        //     ->where('complete_order', $comp)
        //     ->addSelect(['name_client' => Client::select('name')
        //     ->whereColumn('clients.id', 'orders.client_id')])
        //     ->orderBy('order_date')
        //     ->orderBy('order_number')
        //     ->paginate(10);

        // } else {
        //     $q = '';
        // }

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

        return view('orders', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'orders' => $orders,
            'q' => $q,
            'comp' => $comp,
            'orders_repeated' => $orders_repeated
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
        if (!in_array('14', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $client = array();
        if (!empty($_GET['client'])) {
            $client = Client::find($_GET['client']);
        }

        $products = Product::all();
        $user_permissions = Helper::get_permissions();
        $seq_order_number = $this->get_seq_order_number();

        return view('orders_create', [
            'user' => Auth::user(),
            'client' => $client,
            'products' => $products,
            'user_permissions' => $user_permissions,
            'seq_order_number' => $seq_order_number,
        ]);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('14', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('clients.index')->withErrors($message);
        }

        $data = $request->only([
            "order_date",
            "client_name",
            "client_id",
            "order_number",
        ]);

        Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "client_name" => ['required'],
                "client_id" => ['required'],
                "order_number" => ['required', 'unique:orders'],
            ]
        )->validate();

        $order = new Order();
        $order->client_id = $data['client_id'];
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->payment = 'Aberto';
        $order->withdraw = 'Entregar';
        $order->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $order->id, $order->order_number, 'Pedidos');

        // foreach ($data['prod'] as $item) {
        //     if (!empty($item['product_name'])) {

        //         $quant = str_replace('.', '', $item['quant']);

        //         $unit_price = str_replace('.', '', $item['unit_val']);
        //         $unit_price = str_replace(',', '.', $unit_price);

        //         $total_price = str_replace('.', '', $item['total_val']);
        //         $total_price = str_replace(',', '.', $total_price);

        //         $order_prod = new Order_product();
        //         $order_prod->order_id = $data['order_number'];
        //         $order_prod->product_id = $item['product_name'];
        //         $order_prod->quant = $quant;
        //         $order_prod->unit_price = $unit_price;
        //         $order_prod->total_price = $total_price;
        //         $order_prod->delivery_date = $item['delivery_date'];
        //         $order_prod->save();
        //     }
        // }

        return redirect()->route('orders.edit', ['order' => $order->id]);
    }

    public function show($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('17', $user_permissions) && !Auth::user()->is_admin) {
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

        return view('orders_view', [
            'order' => $order,
            'order_products' => $order_products,
            'user_permissions' => $user_permissions,
            'product' => $product,
            'saldo_produtos' => $saldo_produtos
        ]);
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
        if (!in_array('19', $user_permissions) && !Auth::user()->is_admin) {
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

        return view('orders_conclude', [
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
    public function edit($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('18', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $order = Order::addSelect(['name_client' => Client::select('name')
            ->whereColumn('id', 'orders.client_id')])
            ->find($id);

        $order_products = Order_product::where('order_id', $order->order_number)
            ->addSelect(['product_name' => Product::select('name')
                ->whereColumn('id', 'order_products.product_id')])
            ->orderBy('delivery_date')
            ->get();

        $user_permissions = Helper::get_permissions();
        $products = Product::all();

        return view('orders_edit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'order' => $order,
            'order_products' => $order_products,
            'products' => $products,
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
        if (!in_array('18', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('orders.index')->withErrors($message);
        }

        $data = $request->only([
            "order_date",
            "order_number",
            'order_old_number',
            "total_order",
            "payment",
            "withdraw",
        ]);

        if ($data['order_number'] != $data['order_old_number']) {
            $validator = Validator::make($data, ['order_number' => 'unique:orders'])->validate();
        }

        $validator = Validator::make(
            $data,
            [
                "order_date" => ['required'],
                "order_number" => ['required'],
                "order_old_number" => ['required'],
                "total_order" => ['required'],
                "payment" => ['required'],
                "withdraw" => ['required'],
            ]
        )->validate();

        $order_total = str_replace('.', '', $data['total_order']);
        $order_total = str_replace(',', '.', $order_total);

        $order_products_qt = Order_product::where('order_id', $data['order_old_number'])->sum('id');
        if ($order_products_qt <= 0) {
            $message = [
                'no-access' => 'O pedido precisa ter algum produto!',
            ];
            return redirect()->route('orders.edit', ['order' => $id])->withErrors($message);
        }

        $change_order_products = Order_product::where('order_id', $data['order_old_number'])->get();
        foreach ($change_order_products as $item) {
            $item->order_id = $data['order_number'];
            $item->save();
        }

        $order = Order::find($id);
        $order->order_date = $data['order_date'];
        $order->order_number = $data['order_number'];
        $order->order_total = $order_total;
        $order->payment = $data['payment'];
        $order->withdraw = $data['withdraw'];
        $order->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $order->id, $order->order_number, 'Pedidos');

        return redirect()->route('orders.index', ['q' => $order->order_number]);
    }

    public function add_line(Request $request)
    {
        $data = $request->only([
            'id_order',
            "order_id",
            "product_id",
            "quant",
            "unit_price",
            "delivery_date",
        ]);

        Validator::make(
            $data,
            [
                "id_order" => ['required'],
                "order_id" => ['required', 'string'],
                "product_id" => ['required'],
                "quant" => ['required'],
                "unit_price" => ['required'],
                "delivery_date" => ['required', 'date'],
            ]
        )->validate();

        $data['quant'] = str_replace('.', '', $data['quant']);

        $data['unit_price'] = str_replace('.', '', $data['unit_price']);
        $data['unit_price'] = str_replace(',', '.', $data['unit_price']);

        $data['total_price'] = ($data['quant'] * $data['unit_price']) / 1000;

        $add_line = new Order_product();
        $add_line->order_id = $data['order_id'];
        $add_line->product_id = $data['product_id'];
        $add_line->quant = $data['quant'];
        $add_line->unit_price = $data['unit_price'];
        $add_line->total_price = $data['total_price'];
        $add_line->delivery_date = $data['delivery_date'];
        $add_line->save();

        $total_order = Order::where('order_number', $add_line->order_id)->first();
        $total_order->order_total = $total_order->order_total + $data['total_price'];
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
            'unit_price',   // valor unitário
            'delivery_date', // data de entrega
            'total_price',  // total (pode vir do form, senão calculamos)
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
            'unit_price'    => 'required',
            'delivery_date' => 'required',
        ])->validate();

        /**
         * Converte valores do formato brasileiro (1.234,56) para decimal
         * - Primeiro remove os pontos dos milhares
         * - Depois troca vírgula decimal por ponto
         */
        $data['quant']       = (float) str_replace(',', '.', str_replace('.', '', $data['quant']));
        $data['unit_price']  = (float) str_replace(',', '.', str_replace('.', '', $data['unit_price']));

        /**
         * Se o total_price não vier do formulário, calcula multiplicando quantidade * valor unitário
         */
        if (!isset($data['total_price']) || $data['total_price'] === null || $data['total_price'] === '') {
            $data['total_price'] = ($data['quant'] * $data['unit_price']) / 1000;
        } else {
            $data['total_price'] = (float) str_replace(',', '.', str_replace('.', '', $data['total_price']));
        }

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
            $edit_line->unit_price    = $data['unit_price'];
            $edit_line->total_price   = $data['total_price'];
            $edit_line->delivery_date = $data['delivery_date'];
            $edit_line->save();

            /**
             * 2) Recalcula o total da ordem:
             *    Soma todos os total_price de order_products vinculados a esta ordem
             */
            $novoTotal = Order_product::where('order_id', $edit_line->order_id)
                ->sum('total_price');

            /**
             * 3) Atualiza o campo orders.order_total com a soma calculada
             */
            Order::where('order_number', $edit_line->order_id)
                ->update(['order_total' => $novoTotal]);

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

    public function destroy(Order $order)
    {
        dd($order);
    }
}
