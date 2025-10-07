<?php

namespace App\Http\Controllers;

use App\Client;
use App\Order;
use App\Order_product;
use App\Product;
use App\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-relatorios', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $clients  = \App\Client::orderBy('name')->pluck('name')->toArray();
        $sellers  = \App\Seller::orderBy('name')->pluck('name')->toArray();
        $orders   = \App\Order::orderBy('order_number')->pluck('order_number')->toArray();
        $products = \App\Product::orderBy('name')->get(['id', 'name']);

        $defaultIni = now()->startOfMonth()->format('Y-m-d');
        $defaultFin = now()->endOfMonth()->format('Y-m-d');

        return view('reports.reports', [
            'user_permissions' => $user_permissions,
            'products'   => $products,
            'clients'    => $clients,
            'sellers'    => $sellers,
            'orders'     => $orders,

            // valores atuais (preenche o form)
            'product_ids' => (array) $request->input('products', []),
            'cliente'     => $request->input('cliente'),
            'vendedor'    => $request->input('vendedor'),
            'pedido'      => $request->input('pedido'),
            'withdraw'    => $request->input('withdraw', 'todas'),
            'date_field'  => $request->input('date_field', 'delivery'),
            'status'      => $request->input('status', 'pendentes'),
            'date_ini'    => $request->input('date_ini', $defaultIni),
            'date_fin'    => $request->input('date_fin', $defaultFin),
        ]);
    }

    public function reportDelivery(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-relatorios', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        // -------- inputs --------
        $productIds = array_filter((array) $request->input('products', []), fn($v) => strlen((string)$v));
        $withdraw   = $request->input('withdraw', 'todas');        // radios: todas|entregar|retirar
        $dateField  = $request->input('date_field', 'delivery');   // radios: delivery|order
        $status     = $request->input('status', 'pendentes');      // radios: pendentes|realizadas|ambos

        $cliente    = trim((string) $request->input('cliente', '')) ?: null;  // nome único
        $vendedor   = trim((string) $request->input('vendedor', '')) ?: null; // nome único
        $pedido     = trim((string) $request->input('pedido', '')) ?: null;   // order_number único

        // Datas: geração de limites robustos (início do dia / próximo dia 00:00)
        // Datas: aceita dd/mm/yyyy ou yyyy-mm-dd e normaliza em Y-m-d
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

        // ------------- PENDENTES -------------
        $pendingData = collect();
        $totalPending = 0;

        if (in_array($status, ['pendentes', 'ambos'])) {

            // Window function SEM filtro de tempo para o saldo refletir a base inteira
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
            ])
                ->when(!empty($productIds), fn($q) => $q->whereIn('product_id', $productIds));

            $sub = DB::query()->fromSub($baseQuery, 'base')
                ->join('orders', 'orders.order_number', '=', 'base.order_id')
                ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
                ->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id')
                ->where('orders.complete_order', 0)
                ->select('base.*', 'orders.order_date', 'orders.withdraw');

            // filtros comuns
            if (!empty($productIds)) {
                $sub->whereIn('base.product_id', $productIds);
            }
            if ($pedido) {
                $sub->where('orders.order_number', $pedido);
            }
            if ($cliente) {
                $sub->where('clients.name', $cliente);
            }
            if ($vendedor) {
                $sub->where('sellers.name', $vendedor);
            }
            if ($withdraw && strtolower($withdraw) !== 'todas') {
                $sub->whereRaw('LOWER(orders.withdraw) = ?', [mb_strtolower($withdraw)]);
            }

            // período (inclusivo) usando date() do SQLite
            if ($iniObj || $finObj) {
                if ($dateField === 'order') {
                    if ($iniObj && $finObj) {
                        $sub->whereBetween(DB::raw('date(orders.order_date)'), [$iniObj, $finObj]);
                    } elseif ($iniObj) {
                        $sub->where(DB::raw('date(orders.order_date)'), '>=', $iniObj);
                    } elseif ($finObj) {
                        $sub->where(DB::raw('date(orders.order_date)'), '<=', $finObj);
                    }
                } else { // delivery
                    if ($iniObj && $finObj) {
                        $sub->whereBetween(DB::raw('date(base.delivery_date)'), [$iniObj, $finObj]);
                    } elseif ($iniObj) {
                        $sub->where(DB::raw('date(base.delivery_date)'), '>=', $iniObj);
                    } elseif ($finObj) {
                        $sub->where(DB::raw('date(base.delivery_date)'), '<=', $finObj);
                    }
                }
            }

            $rows = $sub->orderBy('base.delivery_date')->orderBy('base.id')->get();

            // carrega relações
            $ids    = $rows->pluck('id')->all();
            $models = Order_product::with('order', 'product', 'order.client', 'order.seller')
                ->whereIn('id', $ids)->get()->keyBy('id');

            $pendingData = collect($rows)->map(function ($r) use ($models) {
                $m = $models[$r->id] ?? (object) (array) $r;
                $m->saldo = (int) ($r->saldo ?? 0);
                return $m;
            })->values();

            // total pendente (igual a tabela)
            $totalPending = $rows->sum(function ($r) {
                $saldo = (int) ($r->saldo ?? 0);
                $quant = (int) $r->quant;
                return $saldo > 0 ? min($saldo, $quant) : 0;
            });
        }

        // ------------- REALIZADAS -------------
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
                // entregas realizadas = saídas (quant < 0)
                ->whereRaw('CAST(order_products.quant AS INTEGER) < 0');

            // filtros comuns
            if (!empty($productIds)) {
                $del->whereIn('order_products.product_id', $productIds);
            }
            if ($pedido) {
                $del->where('orders.order_number', $pedido);
            }
            if ($cliente) {
                $del->where('clients.name', $cliente);
            }
            if ($vendedor) {
                $del->where('sellers.name', $vendedor);
            }
            if ($withdraw && strtolower($withdraw) !== 'todas') {
                $del->whereRaw('LOWER(orders.withdraw) = ?', [mb_strtolower($withdraw)]);
            }

            // período (inclusivo) usando date()
            if ($iniObj || $finObj) {
                if ($dateField === 'order') {
                    if ($iniObj && $finObj) {
                        $del->whereBetween(DB::raw('date(orders.order_date)'), [$iniObj, $finObj]);
                    } elseif ($iniObj) {
                        $del->where(DB::raw('date(orders.order_date)'), '>=', $iniObj);
                    } elseif ($finObj) {
                        $del->where(DB::raw('date(orders.order_date)'), '<=', $finObj);
                    }
                } else { // delivery
                    if ($iniObj && $finObj) {
                        $del->whereBetween(DB::raw('date(order_products.delivery_date)'), [$iniObj, $finObj]);
                    } elseif ($iniObj) {
                        $del->where(DB::raw('date(order_products.delivery_date)'), '>=', $iniObj);
                    } elseif ($finObj) {
                        $del->where(DB::raw('date(order_products.delivery_date)'), '<=', $finObj);
                    }
                }
            }

            $deliveredRows = $del->orderBy('order_products.delivery_date')->orderBy('order_products.id')->get();

            // carrega relações
            $idsDel = $deliveredRows->pluck('id')->all();
            $modelsDel = Order_product::with('order', 'product', 'order.client', 'order.seller')
                ->whereIn('id', $idsDel)->get()->keyBy('id');

            $deliveredData = collect($deliveredRows)->map(function ($r) use ($modelsDel) {
                $m = $modelsDel[$r->id] ?? (object) (array) $r;
                $m->delivered_qty = (int) $r->delivered_qty;
                return $m;
            })->values();

            $totalDelivered = (int) $deliveredRows->sum('delivered_qty');
        }

        // ------------- CSV (se solicitado) -------------
        if (strtolower((string) $request->input('export')) === 'csv') {
            $filename = 'relatorio_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];
            $pendingForCsv   = $pendingData;
            $deliveredForCsv = $deliveredData;
            $statusForCsv    = $status;

            $callback = function () use ($statusForCsv, $pendingForCsv, $deliveredForCsv) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM p/ Excel
                fputcsv($out, [
                    'Status',
                    'Data Pedido',
                    'Pedido',
                    'Cliente',
                    'Contato',
                    'Categoria',
                    'Produto',
                    'Quantidade',
                    'Data Entrega',
                    'Vendedor',
                    'Tipo Entrega'
                ]);

                $emit = function ($row, $statusLabel) use ($out) {
                    $order   = $row->order ?? null;
                    $client  = $order->client ?? null;
                    $seller  = $order->seller ?? null;
                    $prod    = $row->product ?? null;

                    $qty = 0;
                    if ($statusLabel === 'Pendente') {
                        $qty = ($row->saldo > 0) ? min((int)$row->saldo, (int)$row->quant) : 0;
                    } else { // Realizada
                        $qty = (int)($row->delivered_qty ?? 0);
                    }
                    if ($qty <= 0) return;

                    $withdraw = isset($order->withdraw) ? (ucfirst($order->withdraw) . ' (' . (strtolower($order->withdraw) === 'retirar' ? 'FOB' : 'CIF') . ')') : '';

                    fputcsv($out, [
                        $statusLabel,
                        isset($order->order_date) ? date('d/m/Y H:i', strtotime($order->order_date)) : '',
                        $row->order_id ?? '',
                        $client->name ?? '',
                        $client->contact ?? '',
                        $client->category->name ?? '',
                        $prod->name ?? '',
                        $qty,
                        isset($row->delivery_date) ? date('d/m/Y H:i', strtotime($row->delivery_date)) : '',
                        $seller->name ?? '',
                        $withdraw,
                    ]);
                };

                if (in_array($statusForCsv, ['pendentes', 'ambos'])) {
                    foreach ($pendingForCsv as $row) $emit($row, 'Pendente');
                }
                if (in_array($statusForCsv, ['realizadas', 'ambos'])) {
                    foreach ($deliveredForCsv as $row) $emit($row, 'Realizada');
                }
                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Meta pra view
        $meta = [
            'status'          => $status,
            'withdraw'        => $withdraw,
            'date_field'      => $dateField,
            'date_ini'        => $iniObj,
            'date_fin'        => $finObj,
            'cliente'         => $cliente,
            'vendedor'        => $vendedor,
            'pedido'          => $pedido,
            'product_ids'     => $productIds,
            'total_pendentes' => (int) $totalPending,
            'total_realizadas' => (int) $totalDelivered,
        ];

        $selProducts = Product::whereIn('id', $productIds)->get(['id', 'name']);

        return view('reports.reports_delivery', [
            'meta'           => $meta,
            'products'       => $selProducts,
            'pendingData'    => $pendingData,
            'deliveredData'  => $deliveredData,
        ]);
    }
}
