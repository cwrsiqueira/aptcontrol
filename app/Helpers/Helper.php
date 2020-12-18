<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\Order;
use App\Order_product;
use App\Log;
use App\User;

class Helper
{
    public static function saveLog($user_id, $action, $item_id, $item_name, $menu)
    {
        $log = new Log();
        $log->user_id = $user_id;
        $log->action = $action;
        $log->item_id = $item_id;
        $log->item_name = $item_name;
        $log->menu = $menu;
        $log->save();
    }

    public static function get_permissions() {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
    }

    public static function day_delivery_recalc($id_product)
    {
        $product = Product::find($id_product);

        $order_products = Order_product::select('order_products.quant', 'order_products.created_at', 'order_products.order_id', 'order_products.id', 'order_products.delivery_date')
        ->join('orders', 'order_number', 'order_id')
        ->where('product_id', $id_product)
        ->where('orders.complete_order', 0)
        ->orderBy('created_at')
        ->get();
        
        $quant_total = 0;
        for ($i=0; $i < count($order_products); $i++) { 
            if($order_products[$i]->quant >= 0) {
                $delivery_in = '1970-01-01';
                
                $order_balance = Order_product::select(DB::raw('sum(order_products.quant) as saldo'))
                ->join('orders', 'order_number', 'order_id')
                ->where('product_id', $id_product)
                ->where('orders.complete_order', 0)
                ->where('order_products.order_id', $order_products[$i]->order_id)
                ->where('delivery_date', '<=', $order_products[$i]->delivery_date)
                ->first();
                
                if($order_balance->saldo > 0)
                {
                    if($order_balance->saldo < $order_products[$i]->quant)
                    {
                        $quant_total += intval($order_balance->saldo);
                    }
                    else
                    {
                        $quant_total += intval($order_products[$i]->quant);
                    }

                    $days_necessary = ((intval($quant_total)) - $product->current_stock) / $product->daily_production_forecast;
                    
                    if ($days_necessary <= 0) {
                        $days_necessary = 0;
                    }
                    
                    $delivery_in = date('Y-m-d', strtotime(date('Y-m-d').' +'.(ceil($days_necessary)).' days'));

                    if (date('w', strtotime($delivery_in)) == 0) {
                        $delivery_in = date('Y-m-d', strtotime($delivery_in.' +1 days'));
                    }
                } else {
                    $complete_order = Order::where('order_number', $order_products[$i]->order_id)->first();
                    $complete_order->complete_order = 1;
                    $complete_order->save();
                }
                
                $include_date = Order_product::find($order_products[$i]->id);
                $include_date->delivery_date = $delivery_in;
                $include_date->save();
            }
        }
    }
}