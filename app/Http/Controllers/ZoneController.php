<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Zone;
use App\ZoneBairro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ZoneController extends Controller
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
        $zones = Zone::with('bairros')
            ->when($q !== '', function ($query) use ($q) {
                $needle = mb_strtolower(Str::ascii($q));
                $query->where(function ($qb) use ($needle, $q) {
                    $qb->whereRaw('LOWER(unaccent(nome)) LIKE ?', ["%{$needle}%"])
                        ->orWhereHas('bairros', function ($sub) use ($q) {
                            $sub->where('bairro_nome', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return view('logistica.zones.index', [
            'user' => Auth::user(),
            'zones' => $zones,
            'q' => $q,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function create()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('logistica.zones.create', [
            'user' => Auth::user(),
            'zone' => new Zone(),
            'bairrosTexto' => '',
            'user_permissions' => $user_permissions,
        ]);
    }

    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.create', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $request->validate([
            'nome' => 'required|string|max:100|unique:zones,nome',
            'obs' => 'nullable|string',
            'bairros' => 'nullable|string',
        ], [], [
            'nome' => 'Nome da zona',
            'obs' => 'Observações',
            'bairros' => 'Bairros',
        ]);

        $zone = Zone::create([
            'nome' => $request->nome,
            'obs' => $request->obs,
        ]);

        $this->syncBairros($zone, $request->bairros);

        Helper::saveLog(Auth::user()->id, 'Cadastro', $zone->id, $zone->nome, 'Logística - Zonas');

        return redirect()->route('zones.index')->with('success', 'Zona cadastrada com sucesso!');
    }

    public function show(Zone $zone)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.view', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $zone->load('bairros');

        return view('logistica.zones.show', [
            'user' => Auth::user(),
            'zone' => $zone,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function edit(Zone $zone)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $zone->load('bairros');
        $bairrosTexto = $zone->bairros->pluck('bairro_nome')->implode("\n");

        return view('logistica.zones.edit', [
            'user' => Auth::user(),
            'zone' => $zone,
            'bairrosTexto' => $bairrosTexto,
            'user_permissions' => $user_permissions,
        ]);
    }

    public function update(Request $request, Zone $zone)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.update', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $request->validate([
            'nome' => 'required|string|max:100|unique:zones,nome,' . $zone->id,
            'obs' => 'nullable|string',
            'bairros' => 'nullable|string',
        ], [], [
            'nome' => 'Nome da zona',
            'obs' => 'Observações',
            'bairros' => 'Bairros',
        ]);

        $zone->update([
            'nome' => $request->nome,
            'obs' => $request->obs,
        ]);

        $this->syncBairros($zone, $request->bairros);

        Helper::saveLog(Auth::user()->id, 'Alteração', $zone->id, $zone->nome, 'Logística - Zonas');

        return redirect()->route('zones.index')->with('success', 'Zona atualizada com sucesso!');
    }

    public function destroy(Zone $zone)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('zones.delete', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('zones.index')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $id = $zone->id;
        $nome = $zone->nome;
        $zone->delete();

        Helper::saveLog(Auth::user()->id, 'Deleção', $id, $nome, 'Logística - Zonas');

        return redirect()->route('zones.index')->with('success', 'Zona excluída com sucesso!');
    }

    private function syncBairros(Zone $zone, ?string $bairrosInput): void
    {
        $zone->bairros()->delete();

        if (empty(trim($bairrosInput ?? ''))) {
            return;
        }

        $linhas = preg_split('/[\r\n,;]+/', $bairrosInput, -1, PREG_SPLIT_NO_EMPTY);
        $bairros = array_map('trim', $linhas);
        $bairros = array_filter(array_unique($bairros));

        foreach ($bairros as $bairro) {
            if ($bairro !== '') {
                $zone->bairros()->create(['bairro_nome' => $bairro]);
            }
        }
    }
}
