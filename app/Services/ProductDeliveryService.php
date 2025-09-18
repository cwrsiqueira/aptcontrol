<?php

namespace App\Services;

use App\Product;
use App\Order_product;
use App\DeliveryReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductDeliveryService
{
    public function firstFeasibleDate(
        Product $product,
        int $newQty,
        array $options = [],
        ?int $actingUserId = null // usuário que está criando/consultando
    ): ?string {
        // Fallback: se não informado, usa o usuário autenticado
        if ($actingUserId === null) {
            $actingUserId = Auth::id(); // pode continuar null (ex.: CLI)
        }

        $extraLeadDays = $options['extra_lead_days'] ?? 0;
        $hardLimitDays = $options['hard_limit_days'] ?? 365;

        $daily = (int) $product->daily_production_forecast;
        $stock = (int) $product->current_stock;

        $today = Carbon::today();

        // 1) Entregas já agendadas de HOJE pra frente (sempre order_products.delivery_date)
        $scheduledByDate = $this->buildScheduledMap($product->id, $today);

        // 2) Backlog: entregas vencidas (< hoje) e não concluídas
        $backlog = (int) Order_product::query()
            ->join('orders', 'orders.order_number', '=', 'order_products.order_id')
            ->where('order_products.product_id', $product->id)
            ->where('orders.complete_order', 0)
            ->whereDate('order_products.delivery_date', '<', $today->toDateString())
            ->sum('order_products.quant');

        if ($backlog > 0) {
            // agrega backlog em HOJE (ou na segunda se hoje for domingo)
            $backlogDate = $today->copy();
            if ($backlogDate->dayOfWeekIso === 7) { // domingo -> segunda
                $backlogDate->addDay();
            }
            $bkKey = $backlogDate->toDateString();
            $scheduledByDate[$bkKey] = ($scheduledByDate[$bkKey] ?? 0) + $backlog;
        }

        // 3) Reservas: limpa expiradas e monta bloqueios SÓ de terceiros
        $this->purgeExpiredReservations();
        $reservedByOthers = $this->buildReservedByOthersMap($product->id, $today, $actingUserId);

        // 4) ABERTURA de hoje: rolar produção de ONTEM
        //    (ajuste mínimo para não quebrar ao virar o dia)
        $openingStock = $stock + max(0, $daily);

        // 5) Caso daily == 0, ainda sim simulamos (sem produção futura)
        $date        = $today->copy()->addDay()->addDays($extraLeadDays); // mínimo +1 dia para novas entregas
        $delivery_in = null;

        for ($i = 0; $i < $hardLimitDays; $i++) {
            if ($date->dayOfWeekIso !== 7) { // nunca agenda no domingo
                if ($this->canPlaceOnDateStrict(
                    $today,
                    $openingStock,     // <- saldo de abertura já com a produção de ontem
                    $daily,
                    $scheduledByDate,
                    $reservedByOthers,
                    $newQty,
                    $date
                )) {
                    $delivery_in = $date->toDateString();
                    break;
                }
            }
            $date->addDay();
        }

        return $delivery_in;
    }

    private function buildScheduledMap(int $productId, Carbon $fromDate): array
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

        $map = [];
        foreach ($rows as $r) {
            $d = Carbon::parse($r->d);
            if ($d->dayOfWeekIso === 7) { // domingo -> segunda (regra de negócio)
                $d = $d->addDay();
            }
            $key = $d->toDateString();
            $map[$key] = ($map[$key] ?? 0) + (int) $r->total;
        }
        return $map;
    }

    private function purgeExpiredReservations(): void
    {
        DeliveryReservation::where('expires_at', '<=', now())->delete();
    }

    private function buildReservedByOthersMap(int $productId, Carbon $fromDate, ?int $actingUserId): array
    {
        $q = DeliveryReservation::query()
            ->where('product_id', $productId)
            ->whereDate('delivery_date', '>=', $fromDate->toDateString())
            ->where('expires_at', '>', now());

        // EXCLUI as reservas do próprio usuário (se soubermos quem é)
        if ($actingUserId !== null) {
            $q->where('user_id', '!=', $actingUserId);
        }

        $rows = $q->select([
            DB::raw('DATE(delivery_date) as d'),
            DB::raw('SUM(quant) as total')
        ])
            ->groupBy('d')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $d = Carbon::parse($r->d);
            if ($d->dayOfWeekIso === 7) { // domingo -> segunda
                $d = $d->addDay();
            }
            $key = $d->toDateString();
            $map[$key] = ($map[$key] ?? 0) + (int) $r->total;
        }
        return $map;
    }

    /**
     * Simulação dia a dia:
     *  - ENTREGAS usam saldo de abertura do dia;
     *  - RESERVAS DE TERCEIROS não consomem saldo, mas BLOQUEIAM a janela do dia:
     *      deliveries(dia) + reservedByOthers(dia) <= opening(dia)
     *  - PRODUÇÃO entra no fim do dia.
     */
    private function canPlaceOnDateStrict(
        Carbon $today,
        int $stockOpeningToday,   // <- já com produção de ontem
        int $daily,
        array $scheduledByDate,
        array $reservedByOthers,
        int $newQty,
        Carbon $candidateDate
    ): bool {
        // Copia o calendário e insere o novo pedido no dia candidato
        $test = $scheduledByDate;
        $candKey = $candidateDate->toDateString();
        $test[$candKey] = ($test[$candKey] ?? 0) + $newQty;

        // Horizonte até o último dia com entrega
        $keys    = array_keys($test);
        $lastKey = $keys ? max($keys) : $candKey; // 'Y-m-d' funciona lexicograficamente
        $horizon = (strcmp($lastKey, $candKey) >= 0) ? $lastKey : $candKey;

        // Saldo de abertura de HOJE (já rolado)
        $date    = $today->copy();
        $current = $stockOpeningToday;

        while ($date->toDateString() <= $horizon) {
            $dk = $date->toDateString();
            $isSunday = ($date->dayOfWeekIso === 7);

            $deliveries = $isSunday ? 0 : (int) ($test[$dk] ?? 0);
            $reserved   = $isSunday ? 0 : (int) ($reservedByOthers[$dk] ?? 0);

            // A janela do dia precisa caber: entregas + reservas <= saldo de abertura
            if ($deliveries + $reserved > $current) {
                return false;
            }

            // Consome do saldo TANTO as entregas reais quanto as reservas de terceiros
            $current -= ($deliveries + $reserved);

            // Produção entra no fim do dia (fica pro dia seguinte)
            $current += max(0, $daily);

            $date->addDay();
        }

        return true;
    }
}
