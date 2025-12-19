<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductStock;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-produtos');
    }

    private function syncCurrentStock(Product $product): void
    {
        $last = ProductStock::where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $product->current_stock = $last ? (int) $last->stock : 0;
        $product->save();
    }

    public function index(Request $request, $productId)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('products.stock', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('products.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $product = Product::findOrFail($productId);

        $q = trim((string) $request->get('q', ''));

        $stocks = ProductStock::where('product_id', $product->id)
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('notes', 'like', "%{$q}%")
                        ->orWhere('stock', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('product_stocks.index', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'product' => $product,
            'stocks' => $stocks,
            'q' => $q,
        ]);
    }

    public function create($productId)
    {
        $user_permissions = Helper::get_permissions();
        $product = Product::findOrFail($productId);

        return view('product_stocks.create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'product' => $product,
        ]);
    }

    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $request->merge([
            'stock' => (int) str_replace('.', '', (string) $request->input('stock')),
        ]);

        $data = $request->only(['stock', 'stock_date', 'notes']);

        Validator::make($data, [
            'stock' => 'required|integer|min:0',
            'stock_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ])->validate();

        DB::transaction(function () use ($product, $data) {
            ProductStock::create([
                'product_id' => $product->id,
                'stock' => $data['stock'],
                'stock_date' => $data['stock_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncCurrentStock($product);
        });

        return redirect()
            ->route('products.stocks.index', $product->id)
            ->with('success', 'Estoque atualizado com sucesso!');
    }

    public function show($productId, $id)
    {
        $user_permissions = Helper::get_permissions();
        $product = Product::findOrFail($productId);
        $stock = ProductStock::where('product_id', $product->id)->findOrFail($id);

        return view('product_stocks.show', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'product' => $product,
            'stock' => $stock,
        ]);
    }

    public function edit($productId, $id)
    {
        $user_permissions = Helper::get_permissions();
        $product = Product::findOrFail($productId);
        $stock = ProductStock::where('product_id', $product->id)->findOrFail($id);

        return view('product_stocks.edit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'product' => $product,
            'stock' => $stock,
        ]);
    }

    public function update(Request $request, $productId, $id)
    {
        $product = Product::findOrFail($productId);
        $stock = ProductStock::where('product_id', $product->id)->findOrFail($id);
        $request->merge([
            'stock' => (int) str_replace('.', '', (string) $request->input('stock')),
        ]);

        $data = $request->only(['stock', 'stock_date', 'notes']);

        Validator::make($data, [
            'stock' => 'required|integer|min:0',
            'stock_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ])->validate();

        DB::transaction(function () use ($product, $stock, $data) {
            $stock->update([
                'stock' => $data['stock'],
                'stock_date' => $data['stock_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncCurrentStock($product);
        });

        return redirect()
            ->route('products.stocks.index', $product->id)
            ->with('success', 'Registro atualizado com sucesso!');
    }

    public function destroy($productId, $id)
    {
        $product = Product::findOrFail($productId);
        $stock = ProductStock::where('product_id', $product->id)->findOrFail($id);

        DB::transaction(function () use ($product, $stock) {
            $stock->delete();
            $this->syncCurrentStock($product);
        });

        return redirect()
            ->route('products.stocks.index', $product->id)
            ->with('success', 'Registro excluído com sucesso!');
    }
}
