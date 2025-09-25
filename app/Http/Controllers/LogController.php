<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Log;
use App\Helpers\Helper;

class LogController extends Controller
{
    public function index()
    {

        $acao = '%';
        if (!empty($_GET['acao'])) {
            $acao = $_GET['acao'];
        }

        $date_limit = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 month'));

        $user_permissions = Helper::get_permissions();

        $log = Log::select('logs.*', 'users.name')
            ->join('users', 'users.id', 'logs.user_id')
            ->orderBy('logs.created_at', 'desc')
            ->where('logs.created_at', '>', $date_limit)
            ->where('action', 'LIKE', $acao)
            ->paginate(10);

        return view('logs.log', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions,
            'log' => $log,
        ]);
    }
}
