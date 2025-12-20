<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Order_product;
use App\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportPrintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-relatorios');
    }

    public function delivery(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-relatorios', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        // ======= Copiado do seu ReportController@reportDelivery (mesma lógica) =======

        $productIds = array_filter((array) $request->input('products', []), fn($v) => strlen((string) $v));
        $withdraw   = $request->input('withdraw', 'todas');
        $payment    = $request->input('payment');
        $dateField  = $request->input('date_field', 'delivery');
        $status     = $request->input('status', 'pendentes');

        $cliente  = trim((string) $request->input('cliente', '')) ?: null;
        $vendedor = trim((string) $request->input('vendedor', '')) ?: null;
        $pedido   = trim((string) $request->input('pedido', '')) ?: null;

        $parseDate = function (?string $s) {
            if (!$s) return null;
            try {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d');
            } catch (\Exception $e) {
            }
            try {
                return \Carbon\Carbon::parse($s)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        $iniObj = $parseDate($request->input('date_ini'));
        $finObj = $parseDate($request->input('date_fin'));
        if ($iniObj && $finObj && $iniObj > $finObj) {
            [$iniObj, $finObj] = [$finObj, $iniObj];
        }

        // -------- pendentes --------
        $pendingData = collect();
        $totalPending = 0;

        if (in_array($status, ['pendentes', 'ambos'])) {

            $baseQuery = Order_product::select([
                'order_products.id',
                'order_products.order_id',
                'order_products.product_id',
                'order_products.quant',
                'order_products.checkmark',
                'order_products.delivery_date',
                DB::raw("
                    SUM(CAST(quant AS INTEGER)) OVER (
                        PARTITION BY order_id, product_id
                        ORDER BY
                            CASE WHEN CAST(quant AS INTEGER) < 0 THEN 0 ELSE 1 END,
                            delivery_date,
                            id
                        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
                    ) AS saldo
                "),
            ])->when(!empty($productIds), fn($q) => $q->whereIn('product_id', $productIds));

            $sub = DB::query()->fromSub($baseQuery, 'base')
                ->join('orders', 'orders.order_number', '=', 'base.order_id')
                ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
                ->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id')
                ->where('orders.complete_order', 0)
                ->select('base.*', 'orders.order_date', 'orders.withdraw', 'orders.payment', 'clients.name as client_name', 'clients.contact as client_contact', 'sellers.name as seller_name', 'orders.client_id');

            if (!empty($productIds)) $sub->whereIn('base.product_id', $productIds);
            if ($pedido) $sub->where('orders.order_number', $pedido);
            if ($cliente) $sub->where('clients.name', $cliente);
            if ($vendedor) $sub->where('sellers.name', $vendedor);
            if ($withdraw && strtolower($withdraw) !== 'todas') $sub->whereRaw('LOWER(orders.withdraw) = ?', [mb_strtolower($withdraw)]);
            if ($payment) $sub->where('orders.payment', $payment);

            if ($iniObj || $finObj) {
                if ($dateField === 'order') {
                    if ($iniObj && $finObj) $sub->whereBetween(DB::raw('date(orders.order_date)'), [$iniObj, $finObj]);
                    elseif ($iniObj) $sub->where(DB::raw('date(orders.order_date)'), '>=', $iniObj);
                    elseif ($finObj) $sub->where(DB::raw('date(orders.order_date)'), '<=', $finObj);
                } else {
                    if ($iniObj && $finObj) $sub->whereBetween(DB::raw('date(base.delivery_date)'), [$iniObj, $finObj]);
                    elseif ($iniObj) $sub->where(DB::raw('date(base.delivery_date)'), '>=', $iniObj);
                    elseif ($finObj) $sub->where(DB::raw('date(base.delivery_date)'), '<=', $finObj);
                }
            }

            $rows = $sub->orderBy('base.delivery_date')->orderBy('base.id')->get();

            $ids = $rows->pluck('id')->all();
            $models = Order_product::with('order', 'product', 'order.client.category', 'order.seller')
                ->whereIn('id', $ids)->get()->keyBy('id');

            $pendingData = collect($rows)->map(function ($r) use ($models) {
                $m = $models[$r->id] ?? (object) (array) $r;
                $m->saldo = (int) ($r->saldo ?? 0);
                return $m;
            })->values();

            $totalPending = $rows->sum(function ($r) {
                $saldo = (int) ($r->saldo ?? 0);
                $quant = (int) $r->quant;
                return $saldo > 0 ? min($saldo, $quant) : 0;
            });
        }

        // -------- realizadas --------
        $deliveredData = collect();
        $totalDelivered = 0;

        if (in_array($status, ['realizadas', 'ambos'])) {

            $del = Order_product::query()
                ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
                ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
                ->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id')
                ->select([
                    'order_products.*',
                    'orders.order_date',
                    'orders.withdraw',
                    DB::raw('ABS(CAST(order_products.quant AS INTEGER)) AS delivered_qty'),
                ])
                ->whereRaw('CAST(order_products.quant AS INTEGER) < 0');

            if (!empty($productIds)) $del->whereIn('order_products.product_id', $productIds);
            if ($pedido) $del->where('orders.order_number', $pedido);
            if ($cliente) $del->where('clients.name', $cliente);
            if ($vendedor) $del->where('sellers.name', $vendedor);
            if ($withdraw && strtolower($withdraw) !== 'todas') $del->whereRaw('LOWER(orders.withdraw) = ?', [mb_strtolower($withdraw)]);
            if ($payment) $del->where('orders.payment', $payment);

            if ($iniObj || $finObj) {
                if ($dateField === 'order') {
                    if ($iniObj && $finObj) $del->whereBetween(DB::raw('date(orders.order_date)'), [$iniObj, $finObj]);
                    elseif ($iniObj) $del->where(DB::raw('date(orders.order_date)'), '>=', $iniObj);
                    elseif ($finObj) $del->where(DB::raw('date(orders.order_date)'), '<=', $finObj);
                } else {
                    // seu relatório usa created_at nas realizadas
                    if ($iniObj && $finObj) $del->whereBetween(DB::raw('date(order_products.created_at)'), [$iniObj, $finObj]);
                    elseif ($iniObj) $del->where(DB::raw('date(order_products.created_at)'), '>=', $iniObj);
                    elseif ($finObj) $del->where(DB::raw('date(order_products.created_at)'), '<=', $finObj);
                }
            }

            $deliveredRows = $del->orderBy('order_products.delivery_date')->orderBy('order_products.id')->get();

            $idsDel = $deliveredRows->pluck('id')->all();
            $modelsDel = Order_product::with('order', 'product', 'order.client.category', 'order.seller')
                ->whereIn('id', $idsDel)->get()->keyBy('id');

            $deliveredData = collect($deliveredRows)->map(function ($r) use ($modelsDel) {
                $m = $modelsDel[$r->id] ?? (object) (array) $r;
                $m->delivered_qty = (int) $r->delivered_qty;
                return $m;
            })->values();

            $totalDelivered = (int) $deliveredRows->sum('delivered_qty');
        }

        $meta = [
            'status' => $status,
            'withdraw' => $withdraw,
            'payment' => $payment,
            'date_field' => $dateField,
            'date_ini' => $iniObj,
            'date_fin' => $finObj,
            'cliente' => $cliente,
            'vendedor' => $vendedor,
            'pedido' => $pedido,
            'product_ids' => $productIds,
            'total_pendentes' => (int) $totalPending,
            'total_realizadas' => (int) $totalDelivered,
        ];

        $selProducts = Product::whereIn('id', $productIds)->get(['id', 'name']);

        // ======= PDF =======
        $pdf = Pdf::loadView('reports.reports_delivery_pdf', [
            'meta' => $meta,
            'products' => $selProducts,
            'pendingData' => $pendingData,
            'deliveredData' => $deliveredData,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Relatorio-Entregas-' . now()->format('Ymd_His') . '.pdf');
    }

    public function stockAudit(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-relatorios', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $from = $request->get('from') ?: now()->subDays(30)->format('Y-m-d');
        $to = $request->get('to') ?: now()->format('Y-m-d');
        $productId = $request->get('product_id');
        $onlyDivergent = (bool) $request->get('only_divergent');

        $sql = <<<SQL
WITH stock_open AS (
  SELECT ps.product_id,
         date(ps.stock_date) AS day,
         ps.stock AS open_stock
  FROM product_stocks ps
  JOIN (
    SELECT product_id, date(stock_date) AS day, MIN(datetime(created_at)) AS min_ct
    FROM product_stocks
    GROUP BY product_id, date(stock_date)
  ) x
    ON x.product_id = ps.product_id
   AND x.day = date(ps.stock_date)
   AND datetime(ps.created_at) = x.min_ct
),
stock_close AS (
  SELECT ps.product_id,
         date(ps.stock_date) AS day,
         ps.stock AS close_stock
  FROM product_stocks ps
  JOIN (
    SELECT product_id, date(stock_date) AS day, MAX(datetime(created_at)) AS max_ct
    FROM product_stocks
    GROUP BY product_id, date(stock_date)
  ) x
    ON x.product_id = ps.product_id
   AND x.day = date(ps.stock_date)
   AND datetime(ps.created_at) = x.max_ct
),
delivered AS (
  SELECT product_id,
         date(delivery_date) AS day,
         -SUM(quant) AS delivered_qty
  FROM order_products
  WHERE quant < 0 AND delivery_date IS NOT NULL
  GROUP BY product_id, date(delivery_date)
),
next_open AS (
  SELECT product_id,
         day AS next_day,
         open_stock AS next_open_stock
  FROM stock_open
)
SELECT
  p.id AS product_id,
  p.name AS product_name,
  so.day AS day,
  so.open_stock AS open_stock,
  COALESCE(d.delivered_qty, 0) AS delivered_qty,
  sc.close_stock AS close_stock,
  (so.open_stock - COALESCE(d.delivered_qty, 0)) AS expected_close_no_adjust,
  (sc.close_stock - (so.open_stock - COALESCE(d.delivered_qty, 0))) AS implied_adjustment,
  p.daily_production_forecast AS forecast,
  (sc.close_stock + p.daily_production_forecast) AS expected_next_open,
  no.next_open_stock AS next_open_stock,
  (no.next_open_stock - (sc.close_stock + p.daily_production_forecast)) AS divergence_next_day
FROM stock_open so
JOIN stock_close sc
  ON sc.product_id = so.product_id AND sc.day = so.day
JOIN products p
  ON p.id = so.product_id
LEFT JOIN delivered d
  ON d.product_id = so.product_id AND d.day = so.day
LEFT JOIN next_open no
  ON no.product_id = so.product_id AND no.next_day = date(so.day, '+1 day')
WHERE 1=1
SQL;

        $bindings = ['from' => $from, 'to' => $to];
        $sql .= " AND so.day >= :from AND so.day <= :to ";

        if (!empty($productId)) {
            $sql .= " AND p.id = :product_id ";
            $bindings['product_id'] = (int) $productId;
        }

        if ($onlyDivergent) {
            $sql .= " AND no.next_open_stock IS NOT NULL
                      AND (no.next_open_stock - (sc.close_stock + p.daily_production_forecast)) != 0 ";
        }

        $sql .= " ORDER BY p.name, so.day ";

        $rows = DB::select($sql, $bindings);

        $pdf = Pdf::loadView('reports.stock_audit_pdf', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'only_divergent' => $onlyDivergent,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Auditoria-Estoque-' . now()->format('Ymd_His') . '.pdf');
    }
}
