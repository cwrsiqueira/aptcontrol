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
            'version'    => 'v1.0.5',
            'updated_at' => Carbon::now()->format('d/m/Y'),
            'updates'    => [
                'Romaneio de Transporte no PDF do pedido (Detalhes do pedido), com endereço de entrega em pedidos CIF',
                'Relatório Produção pendente no hub Relatórios (saldo a produzir) com geração de PDF em nova aba',
                'Deploy: script deploy.sh com route:clear antes do route:cache (rotas novas após pull)',
                'Listagem Entregas por produto ordenada por data de entrega (próximas primeiro) e filtro CIF consistente',
                'Relatório de entregas “realizadas”: filtro por período usando data de entrega do item',
                'Módulo de logística: caminhões (responsável/manutenção), zonas, cargas, motorista por carga, PDF e remover item da carga',
                'Estoque por produto, auditoria de estoque, hub de relatórios (entregas + CSV), permissões por slug',
                'Logout corrigido para permitir route:cache; backups SQLite (*.backup) ignorados no Git',
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
