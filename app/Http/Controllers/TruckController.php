<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TruckController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-logistica');
    }

    public function index(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-logistica', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $q = trim((string) $request->get('q', ''));
        $trucks = Truck::when($q !== '', function ($query) use ($q) {
            $needle = mb_strtolower(Str::ascii($q));
            $query->where(function ($qb) use ($needle, $q) {
                $qb->whereRaw('LOWER(unaccent(responsavel)) LIKE ?', ["%{$needle}%"])
                    ->orWhere('placa', 'like', "%{$q}%")
                    ->orWhere('modelo', 'like', "%{$q}%");
            });
        })
            ->orderBy('responsavel')
            ->paginate(10)
            ->withQueryString();

        return view('logistica.trucks.index', [
            'user' => Auth::user(),
            'trucks' => $trucks,
            'q' => $q,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function create()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('logistica.trucks.create', [
            'user' => Auth::user(),
            'truck' => new Truck(),
            'user_permissions' => $user_permissions,
        ]);
    }

    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $request->validate([
            'responsavel' => 'required|string|max:150',
            'capacidade_paletes' => 'required|integer|min:0',
            'modelo' => 'nullable|string|max:100',
            'placa' => 'nullable|string|max:20',
            'obs' => 'nullable|string',
        ], [], [
            'responsavel' => 'Responsável',
            'capacidade_paletes' => 'Capacidade (paletes)',
            'modelo' => 'Modelo',
            'placa' => 'Placa',
            'obs' => 'Observações',
        ]);

        $truck = Truck::create($request->only([
            'responsavel', 'capacidade_paletes', 'modelo', 'placa', 'obs'
        ]));

        Helper::saveLog(Auth::user()->id, 'Cadastro', $truck->id, $truck->responsavel, 'Logística - Caminhões');

        return redirect()->route('trucks.index')->with('success', 'Caminhão cadastrado com sucesso!');
    }

    public function show(Truck $truck)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.view', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('logistica.trucks.show', [
            'user' => Auth::user(),
            'truck' => $truck,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function edit(Truck $truck)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('logistica.trucks.edit', [
            'user' => Auth::user(),
            'truck' => $truck,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $request->validate([
            'responsavel' => 'required|string|max:150',
            'capacidade_paletes' => 'required|integer|min:0',
            'modelo' => 'nullable|string|max:100',
            'placa' => 'nullable|string|max:20',
            'obs' => 'nullable|string',
        ], [], [
            'responsavel' => 'Responsável',
            'capacidade_paletes' => 'Capacidade (paletes)',
            'modelo' => 'Modelo',
            'placa' => 'Placa',
            'obs' => 'Observações',
        ]);

        $truck->update($request->only([
            'responsavel', 'capacidade_paletes', 'modelo', 'placa', 'obs'
        ]));

        Helper::saveLog(Auth::user()->id, 'Alteração', $truck->id, $truck->responsavel, 'Logística - Caminhões');

        return redirect()->route('trucks.index')->with('success', 'Caminhão atualizado com sucesso!');
    }

    public function destroy(Truck $truck)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('trucks.delete', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('trucks.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $id = $truck->id;
        $nome = $truck->responsavel;
        $truck->delete();

        Helper::saveLog(Auth::user()->id, 'Deleção', $id, $nome, 'Logística - Caminhões');

        return redirect()->route('trucks.index')->with('success', 'Caminhão excluído com sucesso!');
    }
}
