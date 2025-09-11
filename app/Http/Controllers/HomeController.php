<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    private function systemInfo()
    {
        return [
            'version'    => env('APP_VERSION', 'v1.0.6'),
            'updated_at' => '10/09/2025',
            'updates'    => [
                'Tela de login - Implementa visualizar senha no login',
                'Tela de login - Melhora visual da mensagem do caps lock no login',
                'Menu pedidos - altera ordem dos pedidos por Data do pedido',
                'Menu pedidos - soluciona bug que não aparecia o pedido do cliente no relatório',
                'Editar pedido - Muda botões Salvar e Sair pra Concluir',
            ],
        ];
    }
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // Contagens simples, diretas:
        $pendentes  = DB::table('orders')->where('complete_order', 0)->count();
        $concluidas = DB::table('orders')->where('complete_order', 1)->count();
        $canceladas = DB::table('orders')->where('complete_order', 2)->count();

        // Atrasadas: orders com complete_order == 0 e ALGUM order_products.delivery_date < hoje
        $atrasadas = DB::table('orders as o')
            ->join('order_products as op', 'op.order_id', '=', 'o.order_number')
            ->where('o.complete_order', 0)
            ->whereDate('op.delivery_date', '<', $today)
            ->distinct()                      // importante: distinct global
            ->count('o.order_number');        // conta orders únicas

        // Para hoje: orders com complete_order == 0 e ALGUM order_products.delivery_date == hoje
        $hoje = DB::table('orders as o')
            ->join('order_products as op', 'op.order_id', '=', 'o.order_number')
            ->where('o.complete_order', 0)
            ->whereDate('op.delivery_date', '=', $today)
            ->distinct()
            ->count('o.order_number');

        // (Opcional) bloco manual de versão/atualizações
        $systemInfo = $this->systemInfo();

        $user_permissions = Helper::get_permissions();

        return view('dashboard', [
            'user_permissions' => $user_permissions,
            'cards' => [
                'atrasadas'  => $atrasadas,
                'hoje'       => $hoje,
                'pendentes'  => $pendentes,
                'concluidas' => $concluidas,
                'canceladas' => $canceladas,
            ],
            'systemInfo' => $systemInfo,
        ]);
    }
}
