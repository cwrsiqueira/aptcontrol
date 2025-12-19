<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class StockAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Relatório: segue padrão do sistema (Relatórios = menu-relatorios ou admin)
        // Se você preferir travar com outro slug, ajuste aqui.
        $this->middleware('can:menu-relatorios');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('menu-relatorios', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        // Filtros
        $from = $request->get('from') ?: now()->subDays(30)->format('Y-m-d');
        $to = $request->get('to') ?: now()->format('Y-m-d');
        $productId = $request->get('product_id');
        $onlyDivergent = (bool) $request->get('only_divergent');

        // Paginação
        $perPage = (int) ($request->get('per_page') ?: 20);
        $perPage = max(10, min(100, $perPage));
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $perPage;

        // Lista de produtos pro filtro
        $products = Product::orderBy('name')->get(['id', 'name']);

        // SQL (SQLite) com CTE + filtros no SELECT final
        $baseSql = <<<SQL
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

        $bindings = [
            'from' => $from,
            'to' => $to,
        ];

        // Filtro de período (inclusive)
        $baseSql .= " AND so.day >= :from AND so.day <= :to ";

        // Filtro por produto
        if (!empty($productId)) {
            $baseSql .= " AND p.id = :product_id ";
            $bindings['product_id'] = (int) $productId;
        }

        // Mostrar só divergências
        if ($onlyDivergent) {
            // evita NULL do next_open (sem dia seguinte lançado)
            $baseSql .= " AND no.next_open_stock IS NOT NULL AND (no.next_open_stock - (sc.close_stock + p.daily_production_forecast)) != 0 ";
        }

        // Ordenação
        $baseSql .= " ORDER BY p.name, so.day ";

        // Count total (wrap)
        $countSql = "SELECT COUNT(*) as cnt FROM (" . $baseSql . ") t";
        $total = (int) (DB::selectOne($countSql, $bindings)->cnt ?? 0);

        // Página atual
        $pagedSql = $baseSql . " LIMIT {$perPage} OFFSET {$offset} ";
        $rows = DB::select($pagedSql, $bindings);

        // Paginator
        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('reports.stock_audit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'products' => $products,
            'rows' => $paginator,
            'from' => $from,
            'to' => $to,
            'product_id' => $productId,
            'only_divergent' => $onlyDivergent,
            'per_page' => $perPage,
        ]);
    }
}
