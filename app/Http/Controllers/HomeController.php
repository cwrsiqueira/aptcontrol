<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user_permissions = $this->get_permissions();
        if (Auth::user()->confirmed_user != 0) {
            return view('dashboard', [
                'user_permissions' => $user_permissions,
                'user' => Auth::user(),
            ]);
        } else {
            return view('home', [
                'user_permissions' => $user_permissions,
                'user' => Auth::user(),
            ]);
        } 
    }
}
