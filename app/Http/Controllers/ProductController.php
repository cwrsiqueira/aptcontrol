<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Load;
use App\LoadItem;
use App\Order_product;
use App\Product;
use App\Truck;
use App\Zone;
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
            ->orderBy('orders.bairro')
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

        $orderProductIds = $data->pluck('id')->all();
        $loadItemsPorOp = LoadItem::whereIn('order_product_id', $orderProductIds)
            ->selectRaw('order_product_id, SUM(qtd_paletes) as total')
            ->groupBy('order_product_id')
            ->pluck('total', 'order_product_id');

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
            $totalPaletesItem = Helper::cargaTotalPaletes($paletes);
            $data[$key]['paletes_em_carga'] = (int) ($loadItemsPorOp[$item->id] ?? 0);
            $data[$key]['paletes_total'] = $totalPaletesItem;
            $data[$key]['em_carga'] = ($loadItemsPorOp[$item->id] ?? 0) > 0;
        }

        $cargasPorCaminhao = Load::with(['truck', 'items.zone', 'items.orderProduct.order.client', 'items.orderProduct.product'])
            ->whereHas('items')
            ->orderBy('truck_id')
            ->orderBy('id')
            ->get()
            ->groupBy('truck_id');

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
            'cargas_por_caminhao' => $cargasPorCaminhao,
            'trucks' => Truck::orderBy('responsavel')->get(),
            'zones' => Zone::with('bairros')->orderBy('nome')->get(),
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

    public function addToLoad(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'message' => 'Solicite acesso ao administrador!'], 403);
        }

        $request->validate([
            'order_product_id' => 'required|exists:order_products,id',
            'truck_id' => 'required|exists:trucks,id',
            'motorista' => 'nullable|string|max:150',
            'zone_id' => 'nullable|exists:zones,id',
            'zona_nome' => 'nullable|string|max:100',
            'qtd_paletes' => 'required|integer|min:1',
        ]);

        $op = Order_product::with('order')->findOrFail($request->order_product_id);

        if ($op->order->withdraw !== 'entregar') {
            return response()->json(['ok' => false, 'message' => 'Só é possível adicionar à carga pedidos CIF.'], 422);
        }

        $totalPaletes = Helper::cargaTotalPaletes($op->carga);
        if ($totalPaletes <= 0) {
            return response()->json(['ok' => false, 'message' => 'Todos os paletes já estão em carga ou não existem paletes cadastrados para o pedido.'], 422);
        }

        $qtd = (int) $request->qtd_paletes;
        $jaEmCargas = LoadItem::where('order_product_id', $op->id)->sum('qtd_paletes');
        $disponivel = $totalPaletes - $jaEmCargas;

        if ($qtd > $disponivel) {
            return response()->json(['ok' => false, 'message' => "Quantidade de paletes deve ser no máximo {$disponivel}."], 422);
        }

        $truck = Truck::findOrFail($request->truck_id);
        $capacidade = $truck->capacidade_paletes;

        $zoneId = $request->zone_id ?: null;
        $zonaNome = $request->zona_nome ?: null;
        if (!$zoneId && !$zonaNome) {
            $zonaNome = $op->order->zona ?: 'SEM ZONA';
        }
        if ($zoneId) {
            $zonaNome = null;
        }

        $qtdRestante = $qtd;
        while ($qtdRestante > 0) {
            $load = Load::query()
                ->where('truck_id', $truck->id)
                ->whereRaw('(SELECT COALESCE(SUM(qtd_paletes), 0) FROM load_items WHERE load_items.load_id = loads.id) < ?', [$capacidade])
                ->orderByDesc('id')
                ->first();

            if (!$load) {
                $load = Load::create([
                    'truck_id' => $truck->id,
                    'motorista' => $request->input('motorista'),
                    'status' => 'montagem',
                ]);
            } elseif ($request->filled('motorista')) {
                // Sempre atualiza o motorista ao adicionar à carga existente (evita manter motorista de sessão anterior)
                $load->update(['motorista' => $request->input('motorista')]);
            }

            $totalNoLoad = $load->items()->sum('qtd_paletes');
            $espaco = $capacidade - $totalNoLoad;
            $adicionar = min($qtdRestante, $espaco);

            LoadItem::create([
                'load_id' => $load->id,
                'order_product_id' => $op->id,
                'qtd_paletes' => $adicionar,
                'zone_id' => $zoneId,
                'zona_nome' => $zonaNome,
                'bairro' => $op->order->bairro,
            ]);

            $qtdRestante -= $adicionar;
        }

        Helper::saveLog(Auth::user()->id, 'Adicionar à carga', $op->id, $op->order_id, 'Entregas por produto');

        return response()->json(['ok' => true, 'message' => 'Adicionado à carga com sucesso!']);
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

        $resumoProdutos = [];
        $totalProdutos = 0;
        $totalPaletes = 0;

        foreach ($items as $item) {
            $produto = $item->product_name;

            if (!isset($resumoProdutos[$produto])) {
                $resumoProdutos[$produto] = [
                    'produtos' => 0,
                    'paletes' => [],
                ];
            }

            $resumoProdutos[$produto]['produtos'] += (int) $item->quant;
            $totalProdutos += (int) $item->quant;

            foreach ($item->paletes as $p) {
                [$qt, $cap] = explode('x', $p);
                $qt = (int) $qt;
                $cap = (int) $cap;

                $resumoProdutos[$produto]['paletes'][$cap] =
                    ($resumoProdutos[$produto]['paletes'][$cap] ?? 0) + $qt;

                $totalPaletes += $qt;
            }
        }

        $pdf = PDF::loadView('cc.carga_zona_pdf', [
            'zona' => $zona,
            'items' => $items,
            'resumoProdutos' => $resumoProdutos,
            'totalProdutos' => $totalProdutos,
            'totalPaletes' => $totalPaletes,
            'data' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("carga_zona_{$zona}.pdf");
    }

    public function cargaLoadPdf(Load $load)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            abort(403);
        }

        $load->load(['truck', 'items.zone', 'items.orderProduct.order.client', 'items.orderProduct.product']);

        $items = $load->items->map(function ($li) {
            $op = $li->orderProduct;
            return (object) [
                'order_number' => $op->order->order_number ?? '',
                'client_name' => $op->order->client->name ?? '',
                'client_phone' => $op->order->client->contact ?? '',
                'endereco' => $op->order->endereco ?? '',
                'bairro' => $op->order->bairro ?? $li->bairro,
                'product_name' => $op->product->name ?? '',
                'quant' => $op->quant ?? 0,
                'qtd_paletes' => $li->qtd_paletes,
                'zona' => $li->zone_id ? ($li->zone->nome ?? '') : ($li->zona_nome ?? 'SEM ZONA'),
            ];
        });

        $resumoProdutos = [];
        $totalProdutos = 0;
        $totalPaletes = 0;

        foreach ($load->items as $li) {
            $produto = $li->orderProduct->product->name ?? '';
            if (!isset($resumoProdutos[$produto])) {
                $resumoProdutos[$produto] = ['produtos' => 0, 'paletes' => 0];
            }
            $resumoProdutos[$produto]['produtos'] += $li->orderProduct->quant ?? 0;
            $resumoProdutos[$produto]['paletes'] += $li->qtd_paletes;
            $totalProdutos += $li->orderProduct->quant ?? 0;
            $totalPaletes += $li->qtd_paletes;
        }

        $itemsPorZona = $load->items->groupBy(fn ($li) => $li->zone_id ? ($li->zone->nome ?? '') : ($li->zona_nome ?? 'SEM ZONA'));

        $pdf = PDF::loadView('cc.carga_load_pdf', [
            'load' => $load,
            'truck' => $load->truck,
            'itemsPorZona' => $itemsPorZona,
            'resumoProdutos' => $resumoProdutos,
            'totalProdutos' => $totalProdutos,
            'totalPaletes' => $totalPaletes,
            'data' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $nomeArquivo = $load->motorista ?: $load->truck->responsavel;
        return $pdf->stream("carga_" . \Illuminate\Support\Str::slug($nomeArquivo) . ".pdf");
    }

    public function limparCargaZona($zona)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            abort(403);
        }

        $ids = Order_product::query()
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->where('order_products.marcado_carga', 1)
            ->where('orders.withdraw', 'entregar')
            ->where('orders.complete_order', 0)
            ->where('orders.zona', $zona)
            ->pluck('order_products.id');

        if ($ids->count()) {
            Order_product::whereIn('id', $ids)
                ->update(['marcado_carga' => 0]);
        }

        return redirect()->back()->with(
            'success',
            'Carga da zona ' . strtoupper($zona) . ' limpa com sucesso.'
        );
    }

    public function limparCargaLoad(Load $load)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            abort(403);
        }

        $orderProductIds = $load->items->pluck('order_product_id')->unique();
        $load->items()->delete();

        foreach ($orderProductIds as $opId) {
            $hasOtherLoadItems = LoadItem::where('order_product_id', $opId)->exists();
            if (!$hasOtherLoadItems) {
                Order_product::where('id', $opId)->update(['marcado_carga' => 0]);
            }
        }

        $nomeCarga = $load->motorista ?: $load->truck->responsavel;
        return redirect()->back()->with(
            'success',
            'Carga ' . $nomeCarga . ' limpa com sucesso.'
        );
    }

    public function removeFromLoad(Load $load, Order_product $orderProduct)
    {
        $user_permissions = Helper::get_permissions();

        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return response()->json(['ok' => false, 'message' => 'Solicite acesso ao administrador!'], 403);
        }

        $removidos = LoadItem::where('load_id', $load->id)
            ->where('order_product_id', $orderProduct->id)
            ->delete();

        if ($removidos > 0) {
            $hasOtherLoadItems = LoadItem::where('order_product_id', $orderProduct->id)->exists();
            if (!$hasOtherLoadItems) {
                $orderProduct->update(['marcado_carga' => 0]);
            }
            Helper::saveLog(Auth::user()->id, 'Remover da carga', $orderProduct->id, $orderProduct->order_id, 'Entregas por produto');
        }

        return redirect()->back()->with('success', 'Item removido da carga com sucesso.');
    }
}
