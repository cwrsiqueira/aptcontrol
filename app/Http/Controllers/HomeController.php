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
            'version'    => 'v1.0.7',
            'updated_at' => Carbon::now()->format('d/m/Y'),
            'updates'    => [
                'No cadastro do item do pedido, informe quantos produtos saem em cada entrega. O sistema calcula o número de entregas e deixa na última o que sobrar.',
                'Cada entrega mostra a quantidade, a data prevista e quantos paletes ela ocupa. Quando a entrega é no cliente, a tela indica se já há outras entregas previstas naquele dia.',
                'Novo menu Compras: dá para abrir um pedido de compra, incluir os itens, registrar orçamentos de fornecedores e anexar o arquivo do orçamento em PDF ou imagem.',
                'O pedido de compra segue as etapas de orçamento, aprovação e finalização. Quem pode ver, orçar, aprovar ou concluir fica definido nas permissões.',
                'Na lista de itens do pedido, o botão Adicionar produto ficou ao lado de Imprimir / PDF.',
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
