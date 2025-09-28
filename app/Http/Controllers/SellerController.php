<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Seller;
use App\Helpers\Helper;
use App\Order_product;
use App\Product;

class SellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function contactTypes(): array
    {
        // chaves usadas no banco; labels ficam na view
        return ['whatsapp', 'telefone', 'email', 'instagram', 'outro'];
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        // Exemplo de guarda de acesso à listagem (ajuste os IDs conforme sua regra):
        if (!in_array('menu-vendedores', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $q = trim((string) $request->get('q', ''));

        $sellers = Seller::when($q !== '', function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('contact_value', 'like', "%{$q}%");
        })
            ->orderBy('name')
            ->paginate(10);

        return view('sellers.sellers', [
            'user'             => Auth::user(),
            'sellers'          => $sellers,
            'q'                => $q,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function create()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $seller = new Seller();
        $contactTypes = $this->contactTypes();

        return view('sellers.sellers_create', [
            'user'             => Auth::user(),
            'seller'           => $seller,
            'contactTypes'     => $contactTypes,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $request->validate([
            'name'          => 'required|string|max:150',
            'contact_type'  => 'required|string|in:whatsapp,telefone,email,instagram,outro',
            'contact_value' => 'nullable|string|max:191',
        ]);

        Seller::create($request->only('name', 'contact_type', 'contact_value'));

        return redirect()->route('sellers.index')->with('success', 'Salvo com sucesso!');
    }

    public function show($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $seller = Seller::findOrFail($id);

        return view('sellers.sellers_view', [
            'user'             => Auth::user(),
            'seller'           => $seller,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function edit($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $seller = Seller::findOrFail($id);
        $contactTypes = $this->contactTypes();

        return view('sellers.sellers_edit', [
            'user'             => Auth::user(),
            'seller'           => $seller,
            'contactTypes'     => $contactTypes,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $request->validate([
            'name'          => 'required|string|max:150',
            'contact_type'  => 'required|string|in:whatsapp,telefone,email,instagram,outro',
            'contact_value' => 'nullable|string|max:191',
        ]);

        $seller = Seller::findOrFail($id);
        $seller->update($request->only('name', 'contact_type', 'contact_value'));

        return redirect()->route('sellers.index')->with('success', 'Atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.delete', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        // Bloqueio de exclusão se existir venda vinculada ao vendedor
        $hasOrdersLinked = false;
        if (Schema::hasColumn('orders', 'seller_id')) {
            $count = DB::table('orders')->where('seller_id', $id)->count();
            $hasOrdersLinked = $count > 0;
        }

        if ($hasOrdersLinked) {
            $message = [
                'cannot_exclude' => 'Vendedor possui pedidos vinculados e não pode ser excluído!',
            ];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $seller = Seller::findOrFail($id);
        $seller->delete();

        return redirect()->route('sellers.index')->with('success', 'Excluído com sucesso!');
    }

    public function cc_seller(Request $request, $id)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('sellers.cc', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()
                ->route('sellers.index')
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        // Filtro por produto (ids). Padrão: todos.
        $por_produto = $request->input('por_produto');
        if (empty($por_produto)) {
            $por_produto = Product::pluck('id')->all();
        }

        $seller = Seller::findOrFail($id);
        $complete_order = $request->input('entregas', 0);

        // Linhas dos pedidos em aberto deste cliente, filtradas pelos produtos selecionados
        $data = Order_product::query()
            ->join('orders',   'orders.order_number', '=', 'order_products.order_id')
            ->join('products', 'products.id',         '=', 'order_products.product_id')
            ->where('orders.seller_id', $id)
            ->where('orders.complete_order', $complete_order)
            ->whereIn('order_products.product_id', $por_produto)
            ->orderBy('order_products.delivery_date')
            ->select([
                'order_products.*',
                'orders.order_number as order_id',
                'orders.order_date as order_date',
                'orders.id as orders_order_id',
                'products.name as product_name',
            ])
            ->get();

        // Saldo acumulado por pedido (mesma lógica do código original)
        $saldoPorPedido = [];
        foreach ($data as $k => $row) {
            $pedido = $row->order_id;
            $saldoPorPedido[$pedido] = ($saldoPorPedido[$pedido] ?? 0) + $row->quant;
            $data[$k]->saldo = $saldoPorPedido[$pedido];
        }

        // Se NÃO marcar "entregas realizadas", filtra para mostrar só previstas (saldo > 0 e data válida)
        if (!$request->filled('entregas')) {
            $data = $data
                ->where('saldo', '>', 0)
                ->where('delivery_date', '>', '1970-01-01');
        }

        // Pedidos efetivamente presentes após os filtros (para compor os totais por produto)
        $orderNumbersUsados = $data->pluck('order_id')->unique()->values();

        // Totais por produto nos pedidos presentes em $data (mantém comportamento do original)
        $totais = Order_product::query()
            ->join('orders',   'orders.order_number', '=', 'order_products.order_id')
            ->join('products', 'products.id',         '=', 'order_products.product_id')
            ->whereIn('order_products.order_id', $orderNumbersUsados)
            ->where('orders.complete_order', $complete_order)
            ->groupBy('products.id', 'products.name')
            ->select([
                'products.id   as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_products.quant) as quant_total'),
            ])
            ->get();

        // Estrutura esperada pela view: ['Nome do produto' => ['id' => ..., 'qt' => ...]]
        $product_total = [];
        foreach ($totais as $row) {
            $product_total[$row->product_name] = [
                'id' => $row->product_id,
                'qt' => $row->quant_total,
            ];
        }

        return view('cc.cc_seller', compact(
            'data',
            'seller',
            'product_total',
            'user_permissions',
        ));
    }
}
