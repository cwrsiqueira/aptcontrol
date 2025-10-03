<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\Order_product;
use App\Stockmovement;
use App\Helpers\Helper;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-produtos');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-produtos', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $q = trim((string) $request->input('q'));

        $products = Product::query()
            ->when($q, function ($qb) use ($q) {
                $needle = mb_strtolower(Str::ascii($q));
                $qb->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(unaccent(name)) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('products.products', [
            'user_permissions' => $user_permissions,
            'user' => Auth::user(),
            'products' => $products,
            'q' => $q
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
        if (!in_array('products.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        return view('products.products_create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions
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
        if (!in_array('products.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'stock',
            'forecast',
        ]);

        if (!empty($data['stock'])) {
            $data['stock'] = str_replace('.', '', $data['stock']);
        }
        if (!empty($data['forecast'])) {
            $data['forecast'] = str_replace('.', '', $data['forecast']);
        }

        Validator::make(
            $data,
            [
                'name' => 'required|unique:products|max:100',
                'stock' => 'integer|nullable',
                'forecast' => 'integer|required',
                'file' => 'image|mimes:jpeg,jpg,png|nullable',
            ]
        )->validate();

        if (!empty($data['file'])) {
            $data['file'] = 'preenchido';
        } else {
            $data['file'] = 'não informado';
        }

        $prod = new Product();
        $prod->name = $data['name'];
        $prod->current_stock = $data['stock'] ?? 0;
        $prod->daily_production_forecast = $data['forecast'];
        $prod->img_url = $data['file'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Produtos');

        return redirect()->route('products.index')->with('success', 'Salvo com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        return view('products.products_view', [
            'user'             => Auth::user(),
            'product'           => $product,
            'user_permissions' => $user_permissions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        return view('products.products_edit', [
            'user' => Auth::user(),
            'product' => $product,
            'user_permissions' => $user_permissions
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
        if (!in_array('products.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        if (!empty($request->input('add_stock'))) {
            $data = $request->only([
                'name',
                'add_stock',
                'dt_add_estoque',
            ]);

            $data['add_stock'] = str_replace('.', '', $data['add_stock']);

            $validator = Validator::make(
                $data,
                [
                    'name' => 'required|max:100',
                    'add_stock' => 'integer|nullable',
                    'dt_add_estoque' => 'date|nullable'
                ]
            )->validate();

            $prod = Product::find($id);
            $prod->current_stock = $prod->current_stock + $data['add_stock'];
            $prod->save();

            $mov_stock = new Stockmovement();
            $mov_stock->product_id = $id;
            $mov_stock->movement_date = $data['dt_add_estoque'];
            $mov_stock->movement_quant = $data['add_stock'];
            $mov_stock->save();
        }

        $data = $request->only([
            'name',
            'stock',
            'forecast',
            'file',
        ]);

        $data['stock'] = str_replace('.', '', $data['stock']);
        $data['forecast'] = str_replace('.', '', $data['forecast']);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'stock' => 'integer|nullable',
                'forecast' => 'integer|required',
                'file' => 'image|mimes:jpeg,jpg,png|nullable',
            ]
        )->validate();

        if (!empty($data['file'])) {
            $data['file'] = 'preenchido';
        } else {
            $data['file'] = 'não informado';
        }

        $prod = Product::find($id);
        $prod->name = $data['name'];
        $prod->current_stock = $data['stock'];
        $prod->daily_production_forecast = $data['forecast'] ?? 1;
        $prod->img_url = $data['file'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $prod->id, $prod->name, 'Produtos');

        return redirect()->route('products.index')->with('success', 'Atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.delete', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }

        $order_products = Order_product::where('product_id', $id)->get();
        $stockmovements = Stockmovement::where('product_id', $id)->get();

        if (count($order_products) > 0) {
            $message = [
                'cannot_exclude' => 'Produto possui pedidos vinculados e não pode ser excluído!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        } elseif (count($stockmovements) > 0) {
            $message = [
                'cannot_exclude' => 'Produto possui movimentação e não pode ser excluído!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        } else {
            $product = Product::find($id);
            $del = Product::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $product->name, 'Produtos');
            return redirect()->route('products.index')->with('success', 'Excluído com sucesso!');
        }
    }

    public function cc_product(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('products.index')->withErrors($message);
        }
        // checks pode vir do request (ex.: ?checks[]=1&checks[]=2)
        $checks = (array) $request->input('por_favorito', []); // se vazio => sem filtro (todas as linhas do produto)

        // 1) baseQuery: calcula o saldo usando window function (saidas primeiro)
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
        ")
        ])->where('product_id', $id);

        // 2) transforma em subquery para podermos filtrar sem quebrar a window function
        $sub = DB::query()->fromSub($baseQuery, 'base');

        // 3) aplicar filtro por checkmark se houver (caso contrário pega tudo)
        if (!empty($checks)) {
            $sub = $sub->whereIn('checkmark', $checks);
        }

        // 4) ordenar e recuperar linhas (stdClass) — aqui usamos get() para trabalhar em memória
        $rows = $sub->orderBy('delivery_date')->orderBy('id')->get();

        // 5) extrair ids e map de saldo por id
        $ids = $rows->pluck('id')->all();                 // ids de order_products retornados
        $saldoMap = $rows->pluck('saldo', 'id')->all();   // [ id => saldo, ... ]

        // 6) buscar os modelos Eloquent com relações (para usar no blade)
        $models = Order_product::with('order', 'product', 'order.client', 'order.seller')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id'); // index por id pra montar na ordem depois

        // 7) montar coleção final na MESMA ordem do $rows e anexar o saldo em cada model
        $data = collect($rows)->map(function ($r) use ($models, $saldoMap) {
            $id = $r->id;
            if (! isset($models[$id])) {
                // fallback: se por algum motivo o model não existir, cria um objeto simples
                $m = (object) (array) $r;
                $m->saldo = (int) $saldoMap[$id];
                return $m;
            }
            $model = $models[$id];
            // anexa saldo (inteiro/float conforme sua coluna)
            $model->saldo = is_null($r->saldo) ? 0 : (int) $r->saldo;
            return $model;
        })->values();

        // 8) cálculos agregados:
        // total (soma de todos os saldos exibidos)
        $total_sum = $data->where('saldo', '>', 0)->sum('saldo');

        // soma por checkmark (mesma coleção exibida)
        $sum_by_check = $data
            ->groupBy(function ($item) {
                // garante chave coerente: se checkmark for null, trata como 0
                return (string) (($item->checkmark !== null) ? $item->checkmark : 0);
            })
            ->map(function ($group) {
                return $group->sum('saldo');
            })
            ->toArray();

        $delivery_in = Helper::day_delivery_calc($id);

        // 9) retornar (ou passar para view)
        return view('cc.cc_product', [
            'data'                => $data,                    // coleção de models (cada um tem ->saldo)
            'product'             => Product::find($id),
            'total_sum'           => $total_sum,
            'delivery_in'         => $delivery_in['delivery_in'],
            'quant_total'         => $delivery_in['quant_total'],
            'user_permissions'    => $user_permissions,
            'quant_por_favorito'  => $sum_by_check,
            'checks_filter'       => $checks,
        ]);
    }
}
