<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Permission_group;
use App\Permission_item;
use App\Permission_link;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_permissions = Helper::get_permissions();
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $users = User::select('*')
            ->addSelect(['group_name' => Permission_group::select('name')->whereColumn('permission_groups.id', 'users.confirmed_user')])
            ->orderBy('users.confirmed_user')
            ->orderBy('users.name')
            ->paginate(10);

        $menus = Permission_item::where('group_name', 'Menus')->get();

        return view('permissions.permissions', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'users' => $users,
            'menus' => $menus,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user_permissions = Helper::get_permissions();
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permissions.index')->withErrors($message);
        }

        $users = User::select('*')
            ->addSelect(['group_name' => Permission_group::select('name')->whereColumn('permission_groups.id', 'users.confirmed_user')])
            ->orderBy('users.name')
            ->paginate(10);
        $user_edit = User::find($id);
        $permissions = Permission_item::orderBy('id')->get();
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = [];
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->slug_permission_item;
        }
        foreach ($permissions as $item) {
            $identifier = explode('-', $item['slug']);
            if (count($identifier) >= 2) {
                $item['ident'] = 'pri';
            } else {
                $item['ident'] = 'sub';
            }
        }

        return view('permissions.permissions', [
            'user' => Auth::user(),
            'user_edit' => $user_edit,
            'users' => $users,
            'permissions' => $permissions,
            'user_permissions' => $user_permissions,
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
        if (!Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('permissions.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
            'user_id',
            'perm',
        ]);

        Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'user_id' => 'required',
                'perm' => 'nullable',
            ]
        )->validate();

        $perm = Permission_link::where('id_user', $data['user_id'])->delete();
        $permissoes = ['Sem acessos'];

        if (!empty($data['perm'])) {
            $permissoes = [];
            for ($i = 0; $i < count($data['perm']); $i++) {
                $permissoes[] = $data['perm'][$i];
                $perm = new Permission_link();
                $perm->id_user = $data['user_id'];
                $perm->slug_permission_item = $data['perm'][$i];
                $perm->save();
            }
        }

        $conf_user = User::find($data['user_id']);
        $conf_user->confirmed_user = 2;
        $conf_user->save();

        Helper::saveLog(Auth::user()->id, 'Permissão', implode(',', $permissoes), $conf_user->name, 'Permissões');

        return redirect()->route('permissions.index');
    }
}
