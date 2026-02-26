<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogisticaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-logistica');
    }

    public function index()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-logistica', $user_permissions) && !Auth::user()->is_admin) {
            return redirect()->route('home')->withErrors(['no-access' => 'Solicite acesso ao administrador!']);
        }

        return view('logistica.index', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
        ]);
    }
}
