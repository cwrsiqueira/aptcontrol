<?php

namespace App\Observers;

use App\Order_product;
use Illuminate\Support\Facades\DB;

class OrderProductObserver
{
    /**
     * Recalcula e atualiza complete_order para um order_number específico.
     */
    protected function recomputeOrderCompletion(string|int|null $orderNumber): void
    {
        if (empty($orderNumber)) {
            return;
        }

        $current = DB::table('orders')
            ->where('order_number', $orderNumber)
            ->value('complete_order');

        // Cancelado só muda pela reabertura explícita.
        if ($current === null || (int) $current === 2) {
            return;
        }

        // Existe algum produto com saldo final != 0 nesse pedido?
        $hasOpenBalance = DB::table('order_products')
            ->where('order_id', $orderNumber)
            ->groupBy('product_id')
            ->havingRaw('SUM(CAST(quant AS INTEGER)) <> 0')
            ->exists();

        $complete = $hasOpenBalance ? 0 : 1;

        if ((int) $current === $complete) {
            return;
        }

        DB::table('orders')
            ->where('order_number', $orderNumber)
            ->update(['complete_order' => $complete]);
    }

    /** created */
    public function created(Order_product $model): void
    {
        $this->recomputeOrderCompletion($model->order_id);
    }

    /** updated (tratar possível mudança de order_id) */
    public function updated(Order_product $model): void
    {
        $oldOrder = $model->getOriginal('order_id');
        $newOrder = $model->order_id;

        if ($oldOrder && $oldOrder != $newOrder) {
            $this->recomputeOrderCompletion($oldOrder);
        }
        $this->recomputeOrderCompletion($newOrder);
    }

    /** deleted */
    public function deleted(Order_product $model): void
    {
        // Ao deletar, recomputa com base no order_id original
        $this->recomputeOrderCompletion($model->getOriginal('order_id') ?: $model->order_id);
    }

    /** restored (se usar SoftDeletes) */
    public function restored(Order_product $model): void
    {
        $this->recomputeOrderCompletion($model->order_id);
    }
}
