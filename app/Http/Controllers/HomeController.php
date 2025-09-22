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
            'version'    => 'v1.0.8',
            'updated_at' => '22/09/2025',
            'updates'    => [
                'Implementa cadastro de vendedores',
                'Implementa informações na tabela de conta corrente do produto: Tipo de entrega e vendedor',
                'Implementa informações de vendedores em todas as tabelas e formulários pertinentes',
                'Tira todas as referências e cálculos de valores do sistema, foca somente na quantidade de produtos',
                'Implementa fixar data ao cadastrar/editar produto no pedido',
                'Implementa fixar data ao clicar na data de entrega no conta corrente do produto',
                'Implementa favoritar cliente ao clicar no seu nome no conta corrente do produto',
                'Altera alguns visuais do sistema',
                'Faz pequenos ajustes de performance e segurança',
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
