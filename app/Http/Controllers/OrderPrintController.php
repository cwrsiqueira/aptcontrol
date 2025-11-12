<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Order; // mantém seu namespace atual
use App\Order_product;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPrintController extends Controller
{
    public function show(Order $order)
    {
        // Relacionamentos que a view usa
        $order->load(['client', 'seller', 'orderProducts.product']);

        $order_products = $order->orderProducts()->with('product')->get();

        $saldo_produtos = Order_product::where('order_id', $order->order_number)
            ->select(
                'product_id',
                DB::raw('SUM(order_products.quant) as saldo'),
                DB::raw('SUM(CASE WHEN quant > 0 THEN quant ELSE 0 END) as saldo_inicial')
            )
            ->with('product') // para ter $s->product->name na view
            ->groupBy('product_id')
            ->get();

        // Map de status/badge igual ao da view
        $map = [
            '0' => ['Pendente', 'success'],
            '1' => ['Finalizado', 'warning'],
            '2' => ['Cancelado', 'danger'],
        ];
        [$status, $badge] = $map[$order->complete_order] ?? ['Pendente', 'success'];

        // Se for usar asset('logo.png'), habilite remoto; com caminho local não precisa
        // Pdf::setOption(['isRemoteEnabled' => true]);

        $html = view('reports.order_pdf', compact(
            'order',
            'order_products',
            'saldo_produtos',
            'status',
            'badge'
        ))->render();

        // A4, PB
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        return $pdf->stream('Pedido-' . $order->order_number . '.pdf');
        // Para baixar direto: return $pdf->download(...);
    }
}
