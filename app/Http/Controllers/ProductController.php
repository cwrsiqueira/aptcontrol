<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Order_product;
use App\Product;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf as PDF; // barryvdh/laravel-dompdf

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-produtos');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('menu-produtos', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $q = trim((string) $request->input('q'));

        $products = Product::query()
            ->when($q, function ($qb) use ($q) {
                $needle = mb_strtolower(Str::ascii($q));
                $qb->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(unaccent(name)) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->orderByRaw('CASE WHEN current_stock > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('current_stock')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // adiciona previsão de entrega para usar no Blade
        $products->getCollection()->transform(function ($p) {
            $calc = Helper::day_delivery_calc($p->id);
            $p->delivery_in = $calc['delivery_in'] ?? null;
            $p->quant_total = $calc['quant_total'] ?? 0; // opcional
            return $p;
        });

        return view('products.products', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'products' => $products,
            'q' => $q,
        ]);
    }

    public function create()
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('products.products_create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
        ]);
    }

    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $data = $request->only([
            'name',
            'forecast',
        ]);

        if (!empty($data['forecast'])) {
            $data['forecast'] = str_replace('.', '', $data['forecast']);
        }

        Validator::make(
            $data,
            [
                'name' => 'required|unique:products,name|max:100',
                'forecast' => 'required|integer',
            ]
        )->validate();

        // opcional: se você tiver upload de imagem no form
        Validator::make(
            $request->all(),
            [
                'file' => 'image|mimes:jpeg,jpg,png|nullable',
            ]
        )->validate();

        $prod = new Product();
        $prod->name = $data['name'];

        // Estoque NÃO é mais definido no cadastro. Começa em 0.
        $prod->current_stock = 0;

        $prod->daily_production_forecast = (int) $data['forecast'];
        $prod->img_url = $request->hasFile('file') ? 'preenchido' : 'não informado';
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Produtos');

        return redirect()->route('products.index')->with('success', 'Salvo com sucesso!');
    }

    public function show(Product $product)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.view', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('products.products_view', [
            'user' => Auth::user(),
            'product' => $product,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function edit(Product $product)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('products.products_edit', [
            'user' => Auth::user(),
            'product' => $product,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $data = $request->only([
            'name',
            'forecast',
            'file',
        ]);

        if (!empty($data['forecast'])) {
            $data['forecast'] = str_replace('.', '', $data['forecast']);
        }

        Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'forecast' => 'required|integer',
                'file' => 'image|mimes:jpeg,jpg,png|nullable',
            ]
        )->validate();

        $product->name = $data['name'];

        // IMPORTANTE: current_stock NÃO é alterado aqui.
        // Estoque só pode ser alterado pela tela de Estoque (product_stocks), para manter auditoria.

        $product->daily_production_forecast = (int) $data['forecast'];
        $product->img_url = $request->hasFile('file') ? 'preenchido' : 'não informado';
        $product->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $product->id, $product->name, 'Produtos');

        return redirect()->route('products.index')->with('success', 'Atualizado com sucesso!');
    }

    public function destroy(Product $product)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.delete', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $order_products = Order_product::where('product_id', $product->id)->count();

        // Se você já migrou tudo para product_stocks, aqui você pode impedir exclusão se tiver lançamentos:
        // $hasStocks = \App\ProductStock::where('product_id', $product->id)->exists();
        // if ($hasStocks) { ... }

        if ($order_products > 0) {
            return redirect()->route('products.index')->withErrors([
                'cannot_exclude' => 'Produto possui pedidos vinculados e não pode ser excluído!',
            ]);
        }

        $id = $product->id;
        $name = $product->name;

        $product->delete();

        Helper::saveLog(Auth::user()->id, 'Deleção', $id, $name, 'Produtos');

        return redirect()->route('products.index')->with('success', 'Excluído com sucesso!');
    }

    public function cc_product(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $checks = (array) $request->input('por_favorito', []); // 1 = A, 2 = L
        $entregar = (int) $request->input('entregar', 0);

        $baseQuery = Order_product::select([
            'order_products.id',
            'order_products.order_id',
            'order_products.product_id',
            'order_products.quant',
            'order_products.checkmark',
            'order_products.delivery_date',
            'order_products.carga',
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
        ])->where('product_id', $id);

        $sub = DB::query()->fromSub($baseQuery, 'base')
            ->join('orders', 'orders.order_number', '=', 'base.order_id')
            ->where('orders.complete_order', 0);

        if ($entregar === 1) {
            $sub->where('orders.withdraw', 'entregar');
        }

        $sub->select('base.*');

        if (!empty($checks)) {
            $sub->whereIn('base.checkmark', $checks);
        }

        $rows = $sub
            ->orderBy('orders.zona')
            ->orderBy('base.delivery_date')
            ->orderBy('base.id')
            ->get();

        $ids = $rows->pluck('id')->all();
        $models = Order_product::with('order', 'product', 'order.client', 'order.seller')
            ->whereIn('id', $ids)->get()->keyBy('id');

        $data = collect($rows)->map(function ($r) use ($models) {
            $m = $models[$r->id] ?? (object) (array) $r;
            $m->saldo = (int) ($r->saldo ?? 0);
            return $m;
        })->values();

        $total_sum = $rows->sum(function ($r) {
            $saldo = (int) ($r->saldo ?? 0);
            $quant = (int) $r->quant;
            return $saldo > 0 ? min($saldo, $quant) : 0;
        });

        $sum_by_check = $rows
            ->filter(fn($r) => (int) ($r->saldo ?? 0) > 0)
            ->groupBy(fn($r) => (int) ($r->checkmark ?? 0))
            ->map(fn($group) => $group->sum(function ($r) {
                $saldo = (int) ($r->saldo ?? 0);
                $quant = (int) $r->quant;
                return $saldo > $quant ? $quant : $saldo;
            }))
            ->toArray();

        $sum_by_check += [0 => 0, 1 => 0, 2 => 0];

        $delivery_in = Helper::day_delivery_calc($id);

        foreach ($data as $key => $item) {
            $carga = json_decode($item->carga, true);
            $paletes = ['tipo' => [], 'quant' => []];

            if ($carga) {
                foreach ($carga as $k => $v) {
                    $paletes['tipo'][] = (int) $k;
                    $paletes['quant'][] = (int) $v;
                }
            }

            $data[$key]['carga'] = $paletes;
        }

        $montarCargas = Order_product::query()
            ->select([
                'order_products.carga',
                'order_products.quant',
                'orders.zona',
            ])
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->where('order_products.marcado_carga', 1)
            ->where('orders.withdraw', 'entregar')
            ->where('orders.complete_order', 0)
            ->get()
            ->groupBy('zona')
            ->map(function ($items) {

                $totalProdutos = 0;
                $paletes = [];

                foreach ($items as $item) {
                    $totalProdutos += (int) $item->quant;

                    $carga = json_decode($item->carga, true) ?? [];

                    foreach ($carga as $k => $v) {
                        $a = (int) $k;
                        $b = (int) $v;

                        if ($a <= 0 || $b <= 0) continue;

                        // normaliza erro humano
                        $p = min($a, $b);
                        $cap = max($a, $b);

                        $paletes[$cap] = ($paletes[$cap] ?? 0) + $p;
                    }
                }

                return [
                    'produtos' => $totalProdutos,
                    'paletes' => $paletes,
                ];
            });

        return view('cc.cc_product', [
            'data' => $data,
            'product' => Product::find($id),
            'total_sum' => (int) $total_sum,
            'delivery_in' => $delivery_in['delivery_in'],
            'quant_total' => (int) $delivery_in['quant_total'],
            'user_permissions' => $user_permissions,
            'quant_por_favorito' => $sum_by_check,
            'checks_filter' => $checks,
            'entregar_filter' => $entregar,
            'cargas_montadas' => $montarCargas,
        ]);
    }

    public function marcar_produto(Request $request, Order_product $order_product)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.marcar_produto', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'message' => 'Solicite acesso ao administrador!'], 403);
        }

        $action = $request->input('action');
        $value = $request->input('value');

        if ($order_product->$action == $value) {
            $value = 0;
        }

        $order_product->$action = $value;
        $order_product->save();

        Helper::saveLog(
            Auth::user()->id,
            'Marcação: ' . $action,
            $order_product->id,
            $order_product->order_id,
            'Entregas por produto'
        );

        return response()->json([
            'ok' => true,
            'id' => $order_product->id,
            'action' => $action,
            'value' => $value,
        ]);
    }

    public function toggleCarga(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->back()->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $id = (int) $request->input('order_product_id');

        $op = Order_product::findOrFail($id);

        $op->marcado_carga = $op->marcado_carga ? 0 : 1;
        $op->save();

        return redirect()->back();
    }

    public function cargaZonaPdf($zona)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            abort(403);
        }

        $items = Order_product::query()
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->join('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
            ->where('order_products.marcado_carga', 1)
            ->where('orders.withdraw', 'entregar')
            ->where('orders.complete_order', 0)
            ->where('orders.zona', $zona)
            ->orderBy('orders.bairro')
            ->orderBy('orders.endereco')
            ->orderBy('orders.order_number')
            ->select([
                'orders.order_number',
                'clients.name as client_name',
                'clients.contact as client_phone',
                'orders.endereco',
                'orders.bairro',
                'orders.zona',
                'orders.order_date',
                'order_products.quant',
                'order_products.carga',
                'products.name as product_name',
            ])
            ->get()
            ->map(function ($item) {
                $paletes = [];

                $carga = json_decode($item->carga, true) ?? [];

                foreach ($carga as $k => $v) {
                    $a = (int) $k;
                    $b = (int) $v;
                    if ($a <= 0 || $b <= 0) continue;

                    $p = min($a, $b);
                    $cap = max($a, $b);

                    $paletes[] = "{$p}x{$cap}";
                }

                $item->paletes = $paletes;

                return $item;
            });

        $totalProdutos = 0;
        $resumoPaletes = [];

        foreach ($items as $item) {
            $totalProdutos += (int) $item->quant;

            foreach ($item->paletes as $p) {
                // formato "16x325"
                [$qt, $cap] = explode('x', $p);

                $qt = (int) $qt;
                $cap = (int) $cap;

                $resumoPaletes[$cap] = ($resumoPaletes[$cap] ?? 0) + $qt;
            }
        }

        $pdf = PDF::loadView('cc.carga_zona_pdf', [
            'zona' => $zona,
            'items' => $items,
            'totalProdutos' => $totalProdutos,
            'resumoPaletes' => $resumoPaletes,
            'data' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        // return $pdf->download("carga_zona_{$zona}.pdf");
        return $pdf->stream("carga_zona_{$zona}.pdf");
    }
}
