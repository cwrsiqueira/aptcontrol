<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;
use App\DeliveryReservation;
use App\Helpers\Helper;
use App\Services\ProductDeliveryService;

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
        // Captura apenas os campos esperados (mantém assinatura e payload atuais)
        $data = $request->only([
            'id_order',
            'order_id',
            'product_id',
            'quant',
            'unit_price',
            'delivery_date', // data vista pelo usuário no Blade
        ]);

        try {
            $result = DB::transaction(function () use (&$data) {
                // --- Normalizações mínimas antes do cálculo ---
                $data['quant'] = (int) str_replace('.', '', $data['quant'] ?? '0');

                // --- Carrega e "trava" registros críticos (fila por produto/pedido) ---
                $order   = Order::where('id', $data['id_order'] ?? null)->lockForUpdate()->firstOrFail();
                $product = Product::where('id', $data['product_id'] ?? null)->lockForUpdate()->firstOrFail();

                // --- Calcula a primeira data viável (fonte única da verdade) ---
                $svc = app(ProductDeliveryService::class);
                $suggested = $svc->firstFeasibleDate(
                    $product,
                    (int) $data['quant'],
                    ['extra_lead_days' => 0, 'hard_limit_days' => 365],
                    Auth::id() // ignora reservas do próprio usuário
                );

                if (!$suggested) {
                    // não há capacidade — falha de validação amigável
                    throw ValidationException::withMessages([
                        'delivery_date' => 'Sem capacidade disponível para atender esta quantidade.',
                    ]);
                }

                // Preenche o campo *_confirmation para usar a regra "confirmed"
                $data['delivery_date_confirmation'] = $suggested;

                // --- Validação completa (inclui confirmed da data) ---
                Validator::make(
                    $data,
                    [
                        'id_order'                    => ['required'],
                        'order_id'                    => ['required', 'string'],
                        'product_id'                  => ['required', 'integer', 'exists:products,id'],
                        'quant'                       => ['required', 'integer', 'min:1'],
                        'unit_price'                  => ['required'],
                        'delivery_date'               => ['required', 'date', 'confirmed'],
                        'delivery_date_confirmation'  => ['required', 'date'],
                    ],
                    [
                        'delivery_date.confirmed' => 'A previsão de entrega mudou e precisa ser recalculada.',
                    ]
                )->validate();

                // --- Normalizações finais numéricas ---
                $data['unit_price'] = (float) str_replace(',', '.', str_replace('.', '', $data['unit_price'] ?? '0'));
                $data['total_price'] = ($data['quant'] * $data['unit_price']) / 1000; // mantenho sua regra

                // --- Grava item com a data vigente (igual à sugerida, pois passou no confirmed) ---
                $add = new Order_product();
                $add->order_id      = $data['order_id'];      // = orders.order_number
                $add->product_id    = $data['product_id'];
                $add->quant         = $data['quant'];
                $add->unit_price    = $data['unit_price'];
                $add->total_price   = $data['total_price'];
                $add->delivery_date = $data['delivery_date']; // igual à $suggested aqui
                $add->save();

                // --- Atualiza total do pedido de forma segura ---
                $order->order_total = $order->order_total + $data['total_price'];
                $order->save();

                // --- Log ---
                Helper::saveLog(Auth::id(), 'Alteração', $add->id, $order->id, 'Pedidos');

                // --- Consome reservas do próprio usuário nessa data (se existir o método) ---
                if (method_exists($this, 'consumeUserReservations')) {
                    $this->consumeUserReservations(
                        (int) $data['product_id'],
                        $add->delivery_date,
                        Auth::id(),
                        (int) $data['quant']
                    );
                }

                return ['_ok' => true, 'order_id' => $order->id];
            });

            return redirect()->route('orders.edit', ['order' => $result['order_id']]);
        } catch (ValidationException $e) {
            // Se falhou (inclui caso "confirmed"), devolve old() com a SUGESTÃO no campo delivery_date
            $old = $data;
            if (!empty($data['delivery_date_confirmation'])) {
                $old['delivery_date'] = $data['delivery_date_confirmation'];
            }
            return back()->withErrors($e->errors())->withInput($old);
        }
    }

    private function consumeUserReservations(int $productId, string $deliveryDate, int $userId, int $qty): void
    {
        DB::transaction(function () use ($productId, $deliveryDate, $userId, $qty) {
            $left = $qty;

            $reservations = DeliveryReservation::where('product_id', $productId)
                ->whereDate('delivery_date', $deliveryDate)   // <- order_products.delivery_date
                ->where('user_id', $userId)
                ->where('expires_at', '>', now())
                ->orderBy('expires_at')       // consome as que vencem primeiro
                ->lockForUpdate()             // ok em MySQL/Postgres; em SQLite segue sem efeito
                ->get();

            foreach ($reservations as $r) {
                if ($left <= 0) break;

                $use   = min($left, (int)$r->quant);
                $left -= $use;
                $r->quant = (int)$r->quant - $use;

                if ($r->quant <= 0) {
                    $r->delete();
                } else {
                    $r->save();
                }
            }
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order_product  $order_product
     * @return \Illuminate\Http\Response
     */
    public function order_product_destroy(Order_product $order_product)
    {
        $orderIdForRedirect = DB::transaction(function () use ($order_product) {
            // Recarrega o item garantindo que existe (e pega os dados necessários antes do delete)
            $item = Order_product::findOrFail($order_product->id);

            // Em order_products, o campo order_id referencia orders.order_number
            $order_number = $item->order_id;
            $order        = Order::where('order_number', $order_number)->firstOrFail();

            // 1) Cria RESERVA (TTL 60 min) somente se havia quantidade positiva
            if ((int)$item->quant > 0) {
                DeliveryReservation::create([
                    'order_id'      => $order_number,          // origem opcional (order_number)
                    'product_id'    => $item->product_id,
                    'delivery_date' => $item->delivery_date,   // ATENÇÃO: coluna fica em order_products.*
                    'quant'         => (int)$item->quant,
                    'user_id'       => Auth::id(),
                    'expires_at'    => now()->addMinutes(60),
                ]);
            }

            // 2) Ajusta o total do pedido (se aplicável)
            if ((int)$item->quant > 0) {
                $order->order_total = $order->order_total - $item->total_price;
                $order->save();
            }

            // 3) Remove o item
            $deletedItemId = $item->id; // guarda para o log
            $item->delete();

            // 4) Log
            Helper::saveLog(Auth::id(), 'Alteração', $deletedItemId, $order->id, 'Pedidos');

            // retorna para o redirect fora da transação
            return $order->id;
        });

        return redirect()->route('orders.edit', ['order' => $orderIdForRedirect]);
    }

    public function destroy(Order $order)
    {
        dd($order);
    }
}
