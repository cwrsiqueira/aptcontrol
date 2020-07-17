<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;

class AjaxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit_complete_order() {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];

            $order = Order::find($id);
            $order->complete_order = 1;
            $order->save();

            echo json_encode('Pedido '.$order->order_number.' Concluído com sucesso!');
        }
    }
    
    public function day_delivery_calc() {
        if (!empty($_GET['quant'])) {
            $quant = $_GET['quant'];
            $quant = str_replace('.', '', $quant);
            $id = $_GET['id'];
            $product = Product::find($id);
            $quant_total = Order_product::select('*')
            ->join('orders', 'order_number', 'order_id')
            ->where('order_products.product_id', $id)
            ->where('orders.complete_order', 0)
            ->sum('quant');
            $quant_total = $quant_total + $quant;
            if (!empty($quant_total)) {
                $days_necessary = ((intval($quant_total)) - $product->current_stock) / $product->daily_production_forecast;
                if ($days_necessary <= 0) {
                    $days_necessary = 0;
                }
                $delivery_in = date('Y-m-d', strtotime(date('Y-m-d').' +'.(ceil($days_necessary)+1).' days'));
            } else {
                $delivery_in = date('Y-m-d');
            }
            echo json_encode($delivery_in);
        }
    }

    public function search() {
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $search = $_GET['search'];
            $data = array();

            switch ($search) {
                case 'Client':
                    $data = Client::where('name', 'LIKE', '%'.$q.'%')->get();
                    break;
                case 'Product':
                    $data = Product::where('name', 'LIKE', '%'.$q.'%')->get();
                    break;
                case 'Order':
                    $data = Order::select('id', 'order_number as name')->where('order_number', 'LIKE', '%'.$q.'%')->get();
                    break;
            }

            echo json_encode($data);
        }
    }

    public function search_order_number() {
        if (!empty($_GET['data'])) {
            $order_number = $_GET['data'];

            $numbers = Order::where('order_number', 'LIKE', $order_number.'-%')
            ->orWhere('order_number', $order_number)
            ->get('order_number');
            
            if (count($numbers) > 0) {
                $count = count($numbers);
                $new_number = $order_number.'-'.($count+1);
                echo json_encode($new_number);
            } else {
                echo json_encode($order_number);
            }
        }
    }
}
