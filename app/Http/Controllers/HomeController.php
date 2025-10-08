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
            'version'    => 'v2.0.1',
            'updated_at' => '08/10/2025',
            'updates'    => [
                'NOVO SISTEMA PSDControl',
                'Implementa opção de alterar status do pagamento',
                'Inativa campo número do pedido para edição',
                'Recalcula e grava número do pedido sequencial',
                'Altera nome do sistema para PSDControl',
                'Ajusta filtro de pesquisa por data de entrega do pedido',
                'Altera posições do menu',
                'Outros pequenos ajustes visuais e funcionais',
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
