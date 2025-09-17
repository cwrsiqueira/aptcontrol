<?php

namespace App\Services;

use App\Product;
use App\Order_product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductDeliveryService
{
    /**
     * Retorna a primeira data (Y-m-d) em que é possível entregar $newQty,
     * respeitando: entrega usa saldo de abertura do dia; produção entra no fim do dia;
     * sem entrega em domingo (produção ocorre).
     *
     * $options:
     *   - extra_lead_days (int): gordura extra além do +1 dia mínimo (default 0)
     *   - hard_limit_days (int): limite de busca pra frente (default 365)
     */
    public function firstFeasibleDate(Product $product, int $newQty, array $options = []): ?string
    {
        $extraLeadDays = $options['extra_lead_days'] ?? 0;
        $hardLimitDays = $options['hard_limit_days'] ?? 365;

        $daily = (int) $product->daily_production_forecast;
        $stock = (int) $product->current_stock;

        $today = Carbon::today();

        // Mapa de entregas já agendadas (Y-m-d => int), empurrando qualquer domingo para segunda
        $scheduledByDate = $this->buildScheduleMap($product->id, $today);

        // Data inicial: mínimo +1 dia, com gordura opcional
        $date        = $today->copy()->addDay()->addDays($extraLeadDays);
        $delivery_in = null;

        for ($i = 0; $i < $hardLimitDays; $i++) {
            if ($date->dayOfWeekIso !== 7) { // não agenda em domingo
                if ($this->canPlaceOnDateStrict($today, $stock, $daily, $scheduledByDate, $newQty, $date)) {
                    $delivery_in = $date->toDateString();
                    break;
                }
            }
            $date->addDay();
        }

        return $delivery_in;
    }

    /**
     * Constrói o mapa ['Y-m-d' => total] das entregas já agendadas, da data base pra frente.
     * Se houver entrega marcada num domingo, desloca para segunda na simulação.
     */
    private function buildScheduleMap(int $productId, Carbon $fromDate): array
    {
        $rows = Order_product::query()
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->where('order_products.product_id', $productId)
            ->where('orders.complete_order', 0)
            ->whereDate('order_products.delivery_date', '>=', $fromDate->toDateString())
            ->select([
                DB::raw('DATE(order_products.delivery_date) as d'),
                DB::raw('SUM(order_products.quant) as total')
            ])
            ->groupBy('d')
            ->get();

        $scheduledByDate = [];
        foreach ($rows as $r) {
            $d = Carbon::parse($r->d);
            if ($d->dayOfWeekIso === 7) {  // domingo -> segunda
                $d = $d->addDay();
            }
            $key = $d->toDateString();
            $scheduledByDate[$key] = ($scheduledByDate[$key] ?? 0) + (int) $r->total;
        }

        return $scheduledByDate;
    }

    /**
     * Simula dia a dia com a regra correta:
     * - ENTREGAS usam apenas o saldo de abertura do dia (sem usar produção do mesmo dia);
     * - PRODUÇÃO entra no fim do dia;
     * - Sem entrega em domingo (produção ocorre normalmente).
     * - Novo pedido ($newQty) é tentado em $candidateDate.
     */
    private function canPlaceOnDateStrict(
        Carbon $today,
        int $stock,
        int $daily,
        array $scheduledByDate,
        int $newQty,
        Carbon $candidateDate
    ): bool {
        $test = $scheduledByDate;
        $candKey = $candidateDate->toDateString();
        $test[$candKey] = ($test[$candKey] ?? 0) + $newQty;

        // Horizonte: até o último dia que tenha entrega
        $keys    = array_keys($test);
        $lastKey = $keys ? max($keys) : $candKey;
        $horizon = (strcmp($lastKey, $candKey) >= 0) ? $lastKey : $candKey;

        $date    = $today->copy();
        $current = $stock; // saldo de abertura do primeiro dia

        while ($date->toDateString() <= $horizon) {
            $dk = $date->toDateString();
            $isSunday = ($date->dayOfWeekIso === 7);

            // 1) ENTREGAS com saldo de abertura
            $deliveries = $isSunday ? 0 : (int) ($test[$dk] ?? 0);
            if ($deliveries > $current) {
                return false; // não há saldo de abertura suficiente
            }
            $current -= $deliveries;

            // 2) PRODUÇÃO entra no fim do dia
            $current += $daily;

            $date->addDay();
        }

        return true;
    }
}
