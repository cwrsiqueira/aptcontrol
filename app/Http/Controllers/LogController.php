<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Log;
use Helper;

class LogController extends Controller
{
    public function index() {

        $user_permissions = Helper::get_permissions();
        
        $log = Log::select('logs.*', 'users.name')
        ->join('users', 'users.id', 'logs.user_id')
        ->orderBy('logs.created_at', 'desc')
        ->paginate(10);
        
        return view('log', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'log' => $log,
        ]);
    }
}
