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
            'version'    => 'v1.0.6',
            'updated_at' => Carbon::now()->format('d/m/Y'),
            'updates'    => [
                'Ao imprimir os itens de um pedido, o documento passou a se chamar Romaneio de Transporte e exibe endereço, bairro e zona quando a entrega é feita na obra do cliente.',
                'Novo relatório Produção pendente no menu Relatórios: mostra só os produtos que ainda têm quantidade a produzir; dá para abrir um PDF na hora.',
                'Na tela Entregas por produto, a lista segue a data de entrega — o que está mais próximo no calendário aparece primeiro.',
                'No relatório de entregas, ao escolher entregas já realizadas e um período de datas, o filtro passa a bater certo com a data de entrega.',
                'Logística: cadastro de caminhões e de zonas; montagem de cargas direto na tela de entregas por produto; motorista informado por carga; PDF da carga para o caminhão; dá para tirar só um pedido da carga sem apagar a carga inteira.',
                'Estoque por produto com histórico de lançamentos; auditoria de estoque; relatório de entregas com opção de baixar planilha quando o sistema oferecer essa opção.',
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
