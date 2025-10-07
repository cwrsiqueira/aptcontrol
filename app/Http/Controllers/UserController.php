<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Permission_link;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_permissions = Helper::get_permissions();
        $user = Auth::user();
        return view('profile.profile', compact(
            'user_permissions',
            'user',
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        if (!Auth::user()->is_admin) {
            return redirect()
                ->route('permissions.edit', ['permission' => $user->id])
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $user->password = Hash::make('12345678');
        $user->save();

        Helper::saveLog(Auth::user()->id, 'Reset de senha', $user->id, $user->name, 'Permissões');
        return redirect()->route('permissions.edit', ['permission' => $user->id])->with('success', 'Senha resetada para senha padrão: 12345678');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser != $user) {
            return redirect()
                ->route('users.index')
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        // validação básica
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'password'           => 'nullable|string|min:8|confirmed',
        ], [], [
            'name' => 'nome',
            'password' => 'nova senha',
        ]);

        // atualiza nome
        $user->name = $data['name'];

        // troca de senha (opcional): só se foi informada nova senha
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->is_admin) {
            return redirect()
                ->route('permissions.edit', ['permission' => $id])
                ->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        $user_permissions = Permission_link::where('id_user', $id);
        $user_permissions->delete();
        $user = User::find($id);
        $user->delete();

        return redirect()->route('permissions.index');
    }
}
