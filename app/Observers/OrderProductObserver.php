<?php

namespace App\Observers;

use App\Order_product;
use Illuminate\Support\Facades\DB;

class OrderProductObserver
{
    /**
     * Recalcula e atualiza complete_order para um order_number específico.
     */
    protected function recomputeOrderCompletion($orderNumber): void
    {
        if (empty($orderNumber)) {
            return;
        }

        // Existe algum produto com saldo final != 0 nesse pedido?
        $hasOpenBalance = DB::table('order_products')
            ->where('order_id', $orderNumber)
            ->groupBy('product_id')
            ->havingRaw('SUM(CAST(quant AS INTEGER)) <> 0')
            ->exists();

        // Se não há linhas em order_products para esse order, considere concluído (=1)
        if (!$hasOpenBalance) {
            $hasAnyItem = DB::table('order_products')->where('order_id', $orderNumber)->exists();
            $complete = $hasAnyItem ? 1 : 1; // sem itens também tratamos como concluído
        } else {
            $complete = 0;
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
