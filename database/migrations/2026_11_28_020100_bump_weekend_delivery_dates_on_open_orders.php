<?php

use App\Helpers\Helper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $openOrderNumbers = DB::table('orders')
            ->where('complete_order', 0)
            ->pluck('order_number')
            ->all();

        if ($openOrderNumbers === []) {
            return;
        }

        $orderProducts = DB::table('order_products')
            ->whereIn('order_id', $openOrderNumbers)
            ->where('quant', '>=', 0)
            ->orderBy('id')
            ->get(['id', 'delivery_date']);

        foreach ($orderProducts as $row) {
            $date = $this->dateOnly($row->delivery_date);
            if ($date === null || !Helper::isNonDeliveryDay($date)) {
                continue;
            }

            DB::table('order_products')
                ->where('id', $row->id)
                ->update(['delivery_date' => Helper::nextBusinessDay($date)]);
        }

        $plans = DB::table('order_product_delivery_plans as p')
            ->join('order_products as op', 'op.id', '=', 'p.order_product_id')
            ->whereIn('op.order_id', $openOrderNumbers)
            ->orderBy('p.order_product_id')
            ->orderBy('p.sequence')
            ->get(['p.id', 'p.order_product_id', 'p.sequence', 'p.delivery_date']);

        $usedByProduct = [];

        foreach ($plans as $plan) {
            $productId = (int) $plan->order_product_id;
            $date = $this->dateOnly($plan->delivery_date);
            if ($date === null) {
                continue;
            }

            if (!isset($usedByProduct[$productId])) {
                $usedByProduct[$productId] = [];
            }

            if (Helper::isNonDeliveryDay($date) || isset($usedByProduct[$productId][$date])) {
                $candidate = Helper::isNonDeliveryDay($date)
                    ? Helper::nextBusinessDay($date)
                    : Helper::businessDayAfter($date);

                while (isset($usedByProduct[$productId][$candidate])) {
                    $candidate = Helper::businessDayAfter($candidate);
                }

                DB::table('order_product_delivery_plans')
                    ->where('id', $plan->id)
                    ->update(['delivery_date' => $candidate]);

                $date = $candidate;
            }

            $usedByProduct[$productId][$date] = true;
        }
    }

    public function down(): void
    {
        // Datas históricas de sábado/domingo não são restauradas.
    }

    private function dateOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return date('Y-m-d', strtotime((string) $value . ' 12:00:00'));
    }
};
