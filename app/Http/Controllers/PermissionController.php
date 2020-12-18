<?php

namespace App\Http\Controllers;

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

    public function get_permissions() {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::select('*')
        ->addSelect(['group_name' => Permission_group::select('name')->whereColumn('permission_groups.id', 'users.confirmed_user')])
        ->orderBy('users.confirmed_user')
        ->orderBy('users.name')
        ->paginate(10);

        $user_permissions = $this->get_permissions();
        
        return view('permissions', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'users' => $users,
        ]);
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
    public function edit($id)
    {
        $users = User::select('*')
        ->addSelect(['group_name' => Permission_group::select('name')->whereColumn('permission_groups.id', 'users.confirmed_user')])
        ->orderBy('users.name')
        ->paginate(10);
        $user_edit = User::find($id);
        $permissions = Permission_item::orderBy('slug')->get();
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        foreach ($permissions as $item) {
            $identifier = explode('-', $item['slug']);
            if (count($identifier) > 2) {
                $item['ident'] = 'sub';
            } else {
                $item['ident'] = 'pri';
            }
        }
        
        return view('permissions', [
            'user' => Auth::user(),
            'user_edit' => $user_edit,
            'users' => $users,
            'permissions' => $permissions,
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
        $data = $request->only([
            'name',
            'user_id',
            'permission_item',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'user_id' => 'required',
                'permission_item' => 'nullable',
            ]
        )->validate();
        
        $perm = Permission_link::where('id_user', $data['user_id'])->delete();
        
        if (!empty($data['permission_item'])) {
            for($i = 0; $i < count($data['permission_item']); $i++) {
                $perm = new Permission_link();
                $perm->id_user = $data['user_id'];
                $perm->id_permission_item = $data['permission_item'][$i];
                $perm->save();
            }
        }

        $conf_user = User::find($data['user_id']);
        $conf_user->confirmed_user = 2;
        $conf_user->save();

        return redirect()->route('permissions.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
