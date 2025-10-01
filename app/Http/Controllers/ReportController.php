<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Product;
use App\Order_product;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-relatorios');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('reports.view', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()
                ->route('products.index')
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $complete_order = $request->input('entregas', 0);

        $orderProducts = Order_product::query()
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id', '=', 'orders.client_id')
            ->leftJoin('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id')
            ->where('orders.complete_order', $complete_order)
            ->orderBy('order_products.delivery_date')
            ->select([
                'order_products.*',
                'orders.order_date',
                'orders.order_number as order_id',
                'orders.seller_id',
                'clients.id as client_id',
                'clients.name as client_name',
                'clients.id_categoria as client_id_categoria',
                'clients.is_favorite as client_favorite',
                'clients_categories.name as category_name',
                'sellers.name as seller_name',
            ])
            ->get();

        // Recalcular "saldo" acumulado por pedido (mesma regra: min(acumulado, quant da linha))
        $acc = [];
        foreach ($orderProducts as $k => $row) {
            $pedido = $row->order_id;
            $acc[$pedido] = ($acc[$pedido] ?? 0) + $row->quant;
            $orderProducts[$k]->saldo = ($acc[$pedido] > $row->quant) ? $row->quant : $acc[$pedido];
        }

        // Filtra após calcular saldo (preserva comportamento do original)
        $orderProducts = $orderProducts
            ->where('saldo', '>', 0)
            ->where('delivery_date', '>', '1970-01-01');

        // Totais por categoria (para montar os checkboxes com badges)
        $quant_por_categoria = Order_product::query()
            ->join('orders',  'orders.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id',          '=', 'orders.client_id')
            ->join('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->where('orders.complete_order', $complete_order)
            ->groupBy('clients_categories.id', 'clients_categories.name')
            ->select([
                DB::raw('SUM(order_products.quant) as saldo'),
                'clients_categories.id',
                'clients_categories.name',
            ])
            ->get();

        return view('reports.reports', [
            'orderProducts'       => $orderProducts,
            'user_permissions'    => $user_permissions,
            'quant_por_categoria' => $quant_por_categoria,
        ]);
    }

    public function report_delivery(Request $request)
    {
        // 1) Entrada (com defaults simples)
        $date_ini      = $request->query('date_ini', date('Y-m-01 00:00:00'));   // pode vir null
        $date_fin      = $request->query('date_fin', date('Y-m-t 23:59:59'));   // pode vir null
        $withdraw  = $request->query('withdraw', '%');   // mantém o LIKE '%'
        $productIds = $request->query('por_produto');    // array de IDs (opcional)

        $withdraw = $withdraw == 'Todas' ? '%' : $withdraw;

        // Se não vier filtro de produto, usa todos (ids)
        if (empty($productIds)) {
            $productIds = Product::pluck('id')->all();
        }

        // 2) Query principal (enxuta e performática)
        $items = Order_product::query()
            ->with([
                'product:id,name',
                'order:id,order_number,client_id,seller_id,complete_order,withdraw,order_date',
                'order.client:id,id_categoria,name,full_address,contact',
                'order.client.category:id,name',
                'order.seller:id,name',
            ])
            // RELAÇÃO: orders.order_number ↔ order_products.order_id
            ->join('orders as o', 'o.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id',          '=', 'o.client_id')
            ->join('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->where('o.complete_order', 0)
            ->when($withdraw !== '%', fn($q) => $q->where('o.withdraw', 'LIKE', $withdraw))
            ->whereDate('order_products.delivery_date', '>=', $date_ini . " 00:00:00")
            ->whereDate('order_products.delivery_date', '<=', $date_fin . " 23:59:59")
            ->whereIn('order_products.product_id', $productIds)
            ->orderBy('order_products.delivery_date')
            ->select('order_products.*') // evita colunas duplicadas do join
            ->get();

        // 3) Cálculo do saldo por (product_id, order_id), mantendo sua regra
        //    - acumula quant por chave e define saldo = min(acumulado, quant da linha)
        $acc = [];
        foreach ($items as $k => $row) {
            $product = $row->product_id;
            $acc[$product] = ($acc[$product] ?? 0) + $row->quant;
            $items[$k]->saldo = ($acc[$product] > $row->quant) ? $row->quant : $acc[$product];
        }

        // Se NÃO marcar "entregas realizadas", filtra para mostrar só previstas (saldo > 0 e data válida)
        if (!$request->filled('entregas')) {
            $items = $items
                ->where('saldo', '>', 0)
                ->where('delivery_date', '>', '1970-01-01');
        }

        // 4) Totais por produto (nome → id + soma de saldo)
        $product_total = $items
            ->groupBy(fn($r) => optional($r->product)->name ?? '—')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'id' => $first->product_id,
                    'qt' => $group->sum('saldo'),
                ];
            })
            ->toArray();

        // 5) View
        return view('reports.reports_delivery', [
            'order_products'    => $items,
            'date_ini'          => $date_ini,
            'date_fin'          => $date_fin,
            'product_total'     => $product_total,
        ]);
    }

    public function report_delivery_byPeriod(Request $request)
    {
        // 1) Entrada (com defaults simples)
        $date_ini      = $request->query('date_ini', date('Y-m-01 00:00:00'));   // pode vir null
        $date_fin      = $request->query('date_fin', date('Y-m-t 23:59:59'));   // pode vir null
        $withdraw  = $request->query('withdraw', '%');   // mantém o LIKE '%'
        $productIds = $request->query('por_produto');    // array de IDs (opcional)

        $withdraw = $withdraw == 'Todas' ? '%' : $withdraw;

        // Se não vier filtro de produto, usa todos (ids)
        if (empty($productIds)) {
            $productIds = Product::pluck('id')->all();
        }

        // 2) Query principal (enxuta e performática)
        $items = Order_product::query()
            ->with([
                'product:id,name',
                // orders.order_number é a owner key
                'order:id,order_number,client_id,seller_id,complete_order,withdraw,order_date',
                // traga id_categoria para permitir order.client->category
                'order.client:id,id_categoria,name,full_address,contact',
                'order.client.category:id,name',
                'order.seller:id,name',
            ])
            // RELAÇÃO: orders.order_number ↔ order_products.order_id
            ->join('orders as o', 'o.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id',          '=', 'o.client_id')
            ->join('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->when($withdraw !== '%', fn($q) => $q->where('o.withdraw', 'LIKE', $withdraw))
            ->whereDate('order_products.delivery_date', '>=', $date_ini . ' 00:00:00')
            ->whereDate('order_products.delivery_date', '<=', $date_fin . ' 23:59:59')
            ->whereIn('order_products.product_id', $productIds)
            ->orderBy('order_products.delivery_date')
            ->select('order_products.*') // evita colunas duplicadas do join
            ->get();

        // 3) Cálculo do saldo por (product_id, order_id), mantendo sua regra
        //    - acumula quant por chave e define saldo = min(acumulado, quant da linha)
        $acumulado = [];
        $orders = $items->map(function ($row) use (&$acumulado) {
            $key = $row->product_id . '|' . $row->order_id; // order_id aqui é order_number
            $acumulado[$key] = ($acumulado[$key] ?? 0) + (float) $row->quant;

            $row->saldo = $acumulado[$key] > (float) $row->quant
                ? (float) $row->quant
                : (float) $acumulado[$key];

            return $row;
        })
            ->where('saldo', '<', 0)
            ->where('delivery_date', '>', '1970-01-01')
            ->values();

        // 4) Totais por produto (nome → id + soma de saldo)
        $product_total = $orders
            ->groupBy(fn($r) => optional($r->product)->name ?? '—')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'id' => $first->product_id,
                    'qt' => $group->sum('saldo'),
                ];
            })
            ->toArray();

        // 5) View
        return view('reports.reports_delivery_by_period', [
            'orders'        => $orders,
            'date_ini'          => $date_ini,
            'date_fin'          => $date_fin,
            'product_total' => $product_total,
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
        //
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
        //
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
        //
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
