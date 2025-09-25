<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\User;
use App\Seller;

class SellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function get_permissions()
    {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = [];
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
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

        return view('sellers', [
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

        return view('sellers_create', [
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

        return view('sellers_view', [
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

        return view('sellers_edit', [
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
                'cannot_exclude' => 'Vendedor não pode ser excluído, pois possui vendas vinculadas!',
            ];
            return redirect()->route('sellers.index')->withErrors($message);
        }

        $seller = Seller::findOrFail($id);
        $seller->delete();

        return redirect()->route('sellers.index')->with('success', 'Excluído com sucesso!');
    }
}
