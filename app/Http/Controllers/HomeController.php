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
            'version'    => 'v2.3.0',
            'updated_at' => Carbon::now()->format('d/m/Y'),
            'updates'    => [
                'Módulo de estoque por produto com histórico e auditoria',
                'Remoção de edição direta de estoque no cadastro/edição de produtos',
                'Auditoria diária de estoque (estoque × entregas × previsão)',
                'Hub de relatórios com relatório de entregas e auditoria',
                'Exportação CSV e impressão em PDF (pedidos, entregas e auditoria)',
                'CRUD de permissões com controle por slug',
                'Dashboard focado no operacional (atrasadas, hoje, pendentes, amanhã)',
            ],
        ];
    }

    public function index()
    {
        $today    = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        /*
        |--------------------------------------------------------------------------
        | Pendentes (pedidos em aberto)
        |--------------------------------------------------------------------------
        */
        $pendentes = DB::table('orders')
            ->where('complete_order', 0)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Atrasadas
        | Pedido em aberto que tenha PELO MENOS um item com delivery_date < hoje
        |--------------------------------------------------------------------------
        */
        $atrasadas = DB::table('orders as o')
            ->join('order_products as op', 'op.order_id', '=', 'o.order_number')
            ->where('o.complete_order', 0)
            ->whereDate('op.delivery_date', '<', $today->toDateString())
            ->distinct()
            ->count('o.order_number');

        /*
        |--------------------------------------------------------------------------
        | Para hoje
        | Pedido em aberto que tenha PELO MENOS um item com delivery_date = hoje
        |--------------------------------------------------------------------------
        */
        $hoje = DB::table('orders as o')
            ->join('order_products as op', 'op.order_id', '=', 'o.order_number')
            ->where('o.complete_order', 0)
            ->whereDate('op.delivery_date', '=', $today->toDateString())
            ->distinct()
            ->count('o.order_number');

        /*
        |--------------------------------------------------------------------------
        | Para amanhã
        | Pedido em aberto que tenha PELO MENOS um item com delivery_date = amanhã
        |--------------------------------------------------------------------------
        */
        $amanha = DB::table('orders as o')
            ->join('order_products as op', 'op.order_id', '=', 'o.order_number')
            ->where('o.complete_order', 0)
            ->whereDate('op.delivery_date', '=', $tomorrow->toDateString())
            ->distinct()
            ->count('o.order_number');

        $systemInfo = $this->systemInfo();
        $user_permissions = Helper::get_permissions();

        return view('dashboard', [
            'user_permissions' => $user_permissions,
            'cards' => [
                'atrasadas' => $atrasadas,
                'hoje'      => $hoje,
                'amanha'    => $amanha,
                'pendentes' => $pendentes,
            ],
            'systemInfo' => $systemInfo,
        ]);
    }
}
