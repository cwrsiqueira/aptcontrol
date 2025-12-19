<?php

namespace App\Http\Controllers;

use App\Permission_item;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Permission_link;
use Illuminate\Support\Facades\DB;

class PermissionItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();

        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $q = trim((string) $request->get('q', ''));

        $items = Permission_item::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('slug', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('group_name', 'like', "%{$q}%");
            })
            ->orderBy('group_name')
            ->orderBy('name')
            ->paginate(15)
            ->appends(['q' => $q]);

        return view('permission_items.index', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'items' => $items,
            'q' => $q,
        ]);
    }

    public function create()
    {
        $user_permissions = Helper::get_permissions();

        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permission-items.index')->withErrors($message);
        }

        return view('permission_items.create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permission-items.index')->withErrors($message);
        }

        $data = $request->only(['slug', 'name', 'group_name']);

        Validator::make($data, [
            'slug' => 'required|string|max:100|unique:permission_items,slug',
            'name' => 'required|string|max:150',
            'group_name' => 'nullable|string|max:100',
        ])->validate();

        $item = Permission_item::create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'group_name' => $data['group_name'] ?? null,
        ]);

        Helper::saveLog(Auth::user()->id, 'Permissões', 'Criou permissão: ' . $item->slug, $item->name, 'Permissões');

        return redirect()->route('permission-items.index')->with('success', 'Permissão criada com sucesso!');
    }

    public function edit($id)
    {
        $user_permissions = Helper::get_permissions();

        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permission-items.index')->withErrors($message);
        }

        $item = Permission_item::findOrFail($id);

        return view('permission_items.edit', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'item' => $item,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permission-items.index')->withErrors($message);
        }

        $item = Permission_item::findOrFail($id);

        $data = $request->only(['slug', 'name', 'group_name']);
        $oldSlug = $item->slug;
        $newSlug = $data['slug'] ?? $oldSlug;

        // Se o slug mudou, exige confirmação
        if ($newSlug !== $oldSlug) {
            $request->validate([
                'confirm_slug_change' => 'accepted',
            ], [
                'confirm_slug_change.accepted' => 'Marque a confirmação para alterar o slug (você precisa refatorar o código).',
            ]);
        }

        $request->validate([
            'slug' => 'required|string|max:100|unique:permission_items,slug,' . $item->id,
            'name' => 'required|string|max:150',
            'group_name' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($item, $data, $oldSlug, $newSlug) {

            // Atualiza o permission_item
            $item->update([
                'slug' => $newSlug,
                'name' => $data['name'],
                'group_name' => $data['group_name'] ?? null,
            ]);

            // Se mudou slug, “carrega junto” as permissões já concedidas aos usuários
            if ($newSlug !== $oldSlug) {
                Permission_link::where('slug_permission_item', $oldSlug)
                    ->update(['slug_permission_item' => $newSlug]);
            }
        });

        return redirect()
            ->route('permission-items.index')
            ->with('success', 'Permissão atualizada com sucesso!');
    }

    public function destroy($id)
    {
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permission-items.index')->withErrors($message);
        }

        $item = Permission_item::findOrFail($id);

        // OBS: como permission_links grava slug, apagar um item pode “órfãos” em permission_links.
        // Se você quiser impedir exclusão quando estiver em uso, me diga e eu travo aqui.
        $slug = $item->slug;
        $name = $item->name;

        $item->delete();

        Helper::saveLog(Auth::user()->id, 'Permissões', 'Excluiu permissão: ' . $slug, $name, 'Permissões');

        return redirect()->route('permission-items.index')->with('success', 'Permissão excluída com sucesso!');
    }
}
