<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Seller;
use App\Product;
use App\Order;
use App\Client;
use App\Order_product;
use App\Stockmovement;
use App\Clients_category;
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

    public function cc_product(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.cc', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()
                ->route('products.index')
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        // Filtro por categoria (checkboxes)
        $cats = (array) $request->input('por_categoria', []);
        // (opcional) normalizar para int: $cats = array_map('intval', $cats);

        $product = Product::findOrFail($id);
        $complete_order = $request->input('entregas', 0);

        // Base: pedidos em aberto do produto
        $data = Order_product::query()
            ->join('orders',  'orders.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id',         '=', 'orders.client_id')
            ->leftJoin('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->leftJoin('sellers',            'sellers.id',            '=', 'orders.seller_id')
            ->where('order_products.product_id', $id)
            ->where('orders.complete_order', $complete_order)
            ->when(!empty($cats), fn($q) => $q->whereIn('clients.id_categoria', $cats))
            ->orderBy('order_products.delivery_date')
            ->select([
                'order_products.*',                   // inclui id, product_id, quant, delivery_date, favorite_delivery, withdraw...
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
        foreach ($data as $k => $row) {
            $pedido = $row->order_id;
            $acc[$pedido] = ($acc[$pedido] ?? 0) + $row->quant;
            $data[$k]->saldo = ($acc[$pedido] > $row->quant) ? $row->quant : $acc[$pedido];
        }

        // Filtra após calcular saldo (preserva comportamento do original)
        $data = $data
            ->where('saldo', '>', 0)
            ->where('delivery_date', '>', '1970-01-01');

        // Totais por categoria (para montar os checkboxes com badges)
        $quant_por_categoria = Order_product::query()
            ->join('orders',  'orders.order_number', '=', 'order_products.order_id')
            ->join('clients', 'clients.id',          '=', 'orders.client_id')
            ->join('clients_categories', 'clients_categories.id', '=', 'clients.id_categoria')
            ->where('order_products.product_id', $id)
            ->where('orders.complete_order', $complete_order)
            ->groupBy('clients_categories.id', 'clients_categories.name')
            ->select([
                DB::raw('SUM(order_products.quant) as saldo'),
                'clients_categories.id',
                'clients_categories.name',
            ])
            ->get();

        // Mantém seu cálculo existente
        $day_delivery_calc = Helper::day_delivery_calc($id);
        $quant_total = $day_delivery_calc['quant_total'];
        $delivery_in = $day_delivery_calc['delivery_in'];

        return view('cc.cc_product', [
            'data'                => $data,
            'product'             => $product,
            'quant_total'         => $quant_total,
            'delivery_in'         => $delivery_in,
            'user_permissions'    => $user_permissions,
            'quant_por_categoria' => $quant_por_categoria,
        ]);
    }

    public function day_delivery_recalc($id_product)
    {
        Helper::day_delivery_recalc($id_product);
        Helper::saveLog(Auth::user()->id, 'Alteração', $id_product, 'Recalc Data Entrega', 'Produtos');
        return redirect()->route('cc_product', ['id' => $id_product]);
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
            return redirect()->route('home')->withErrors($message);
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
            $action = 'Não Autorizado';
        }
        if (!in_array('products.stock', $user_permissions) && !Auth::user()->is_admin) {
            $action = 'Não Autorizado';
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
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        }

        $products = Order_product::where('product_id', $id)->get();
        $stockmovements = Stockmovement::where('product_id', $id)->get();

        if (count($products) > 0) {
            $message = [
                'cannot_exclude' => 'Produto já possui pedidos vinculados e não pode mais ser excluído!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        } elseif (count($stockmovements) > 0) {
            $message = [
                'cannot_exclude' => 'Produto já possui movimentação e não pode ser excluído!',
            ];
            return redirect()->route('products.index')->withErrors($message);
        } else {
            $product = Product::find($id);
            $del = Product::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $product->name, 'Produtos');
            return redirect()->route('products.index')->with('success', 'Excluído com sucesso!');
        }
    }
}
