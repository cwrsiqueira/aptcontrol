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
                    $date,
                    $backlog
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
     * Simulação dia a dia (com "carry" do excedente):
     *  - ENTREGAS usam saldo de abertura do dia;
     *  - RESERVAS DE TERCEIROS reduzem a janela do dia (não são carregadas para frente);
     *  - Se ENTREGAS (incl. novo pedido) > capacidade restante do dia, o excedente vira BACKLOG
     *    e é drenado nos próximos DIAS ÚTEIS antes de prometer novas datas;
     *  - PRODUÇÃO entra no fim do dia (disponível no dia seguinte);
     *  - Nunca entrega aos domingos.
     */
    private function canPlaceOnDateStrict(
        Carbon $today,
        int $stockOpeningToday,   // abertura de HOJE (já com produção de ontem)
        int $daily,
        array $scheduledByDate,
        array $reservedByOthers,
        int $newQty,
        Carbon $candidateDate,
        int $backlogStart = 0
    ): bool {
        // Calendário de teste com o novo pedido
        $test = $scheduledByDate;
        $candKey = $candidateDate->toDateString();
        $test[$candKey] = ($test[$candKey] ?? 0) + $newQty;

        // Horizonte até o último dia com entrega (inclui o candidato)
        $keys    = array_keys($test);
        $lastKey = $keys ? max($keys) : $candKey; // 'Y-m-d' compara lexicograficamente bem
        $horizon = (strcmp($lastKey, $candKey) >= 0) ? $lastKey : $candKey;

        // Estado da simulação
        $date    = $today->copy();
        $current = $stockOpeningToday;
        $backlog = max(0, (int) $backlogStart); // atrasados a quitar ao longo dos dias

        while ($date->toDateString() <= $horizon) {
            $dk       = $date->toDateString();
            $isSunday = ($date->dayOfWeekIso === 7);

            // Quantidades do dia (domingo não entrega)
            $deliveries = $isSunday ? 0 : (int) ($test[$dk] ?? 0);
            $reserved   = $isSunday ? 0 : (int) ($reservedByOthers[$dk] ?? 0);

            // Reserva de terceiros não pode, sozinha, estourar a abertura
            if ($reserved > $current) {
                return false; // dia impossível (excesso de reserva) -> falha
            }

            // Capacidade real do dia para "entrega/backlog" depois de descontar reservas
            $capacity = $current - $reserved;

            if (!$isSunday) {
                // 1) Quita BACKLOG primeiro
                $useBacklog = min($backlog, $capacity);
                $backlog   -= $useBacklog;
                $capacity  -= $useBacklog;

                // 2) Atende ENTREGAS do próprio dia com a capacidade restante
                $servedToday    = min($deliveries, $capacity);
                $deliveriesLeft = $deliveries - $servedToday;

                // 3) O excedente de entregas de hoje vira BACKLOG para os próximos dias úteis
                $backlog += $deliveriesLeft;
            }

            // Fim do dia: entra a produção (fica disponível no dia seguinte)
            $current = $capacity + max(0, $daily);

            $date->addDay();
        }

        // Só aceitamos a data se, até o fim do dia candidato, todo o passado foi "pago"
        return $backlog === 0;
    }
}
