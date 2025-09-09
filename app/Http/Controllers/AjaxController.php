<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Order;
use App\Client;
use App\Product;
use App\Order_product;
use App\User;
use App\Helpers\Helper;

class AjaxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit_complete_order()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];

            $order = Order::find($id);
            $order->complete_order = 1;
            $order->save();

            echo json_encode('Pedido ' . $order->order_number . ' Concluído com sucesso!');
        }
    }

    public function day_delivery_calc()
    {
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
                    $days_necessary = 1;
                }

                $delivery_in = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . (ceil($days_necessary)) . ' days'));

                if (date('w', strtotime($delivery_in)) == 0) {
                    $delivery_in = date('Y-m-d', strtotime($delivery_in . ' +1 days'));
                }
            } else {
                $delivery_in = date('Y-m-d');
            }
            echo json_encode($delivery_in);
        }
    }

    public function search()
    {
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $search = $_GET['search'];
            $data = array();

            switch ($search) {
                case 'Client':
                    $data = Client::where('name', 'LIKE', '%' . $q . '%')->get();
                    break;
                case 'Product':
                    $data = Product::where('name', 'LIKE', '%' . $q . '%')->get();
                    break;
                case 'Order':
                    $data = Order::select('id', 'order_number as name')->where('order_number', 'LIKE', '%' . $q . '%')->get();
                    break;
            }

            echo json_encode($data);
        }
    }

    public function search_order_number()
    {
        if (!empty($_GET['data'])) {
            $order_number = $_GET['data'];

            $numbers = Order::where('order_number', 'LIKE', $order_number . '-%')
                ->orWhere('order_number', $order_number)
                ->get('order_number');

            if (count($numbers) > 0) {
                $count = count($numbers);
                $new_number = $order_number . '-' . ($count + 1);
                echo json_encode($new_number);
            } else {
                echo json_encode($order_number);
            }
        }
    }

    public function saldo_produto()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];
            $order = $_GET['order'];

            $data = Order_product::select(DB::raw('sum(order_products.quant) as saldo'))
                ->join('orders', 'orders.order_number', 'order_products.order_id')
                ->where('product_id', $id)
                ->where('order_id', $order)
                ->groupBy('order_products.order_id')
                ->first();

            echo json_encode($data);
        }
    }

    public function register_delivery()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];
            $id_prod = $_GET['id_prod'];
            $quant = $_GET['quant'];
            $delivered = $_GET['delivered'];

            $order = Order::find($id);
            $order_product = Order_product::where('order_id', $order->order_number)
                ->where('product_id', $id_prod)
                ->first();

            $checkoutorder = new Order_product();
            $checkoutorder->order_id = $order->order_number;
            $checkoutorder->product_id = $order_product->product_id;
            $checkoutorder->quant = $quant * (-1);
            $checkoutorder->unit_price = $order_product->unit_price;
            $checkoutorder->total_price = ($quant * $order_product->unit_price) / 1000;
            $checkoutorder->delivery_date = '1970-01-01';
            $checkoutorder->save();

            if ($delivered == 'total') {

                $total_produtos = Order_product::where('order_id', $order->order_number)
                    ->select(DB::raw("sum(order_products.quant) as saldo"))
                    ->groupBy('order_id')
                    ->first();

                if (intval($total_produtos['saldo']) == 0) {
                    $order->complete_order = 1;
                    $order->save();
                }
            }

            Helper::saveLog(Auth::user()->id, 'Registro de Entrega', $order->id, $order->order_number, 'Pedidos');

            return $id;
        }
    }

    public function register_cancel()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];

            $order = Order::find($id);
            $order->complete_order = 2;
            $order->save();

            Helper::saveLog(Auth::user()->id, 'Cancelamento', $order->id, $order->order_number, 'Pedidos');

            return $id;
        }
    }

    public function update_admin()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];

            $adm = User::find($id);

            if ($adm['confirmed_user'] == 1) {
                $adms = User::where('confirmed_user', 1)->count();
                if ($adms > 1) {
                    $adm->confirmed_user = 2;
                }
            } else {
                $adm->confirmed_user = 1;
            }

            $adm->save();
        }
    }

    public function del_line()
    {
        if (!empty($_GET['n_line'])) {
            $n_line = $_GET['n_line'];
            $total = $_GET['total'];
            $order_prod = Order_product::find($n_line);
            $order = Order::where('order_number', $order_prod->order_id)->first();
            $order->order_total = $total;
            $order->save();
            Order_product::find($n_line)->delete();
        }
    }

    public function del_dup_order()
    {
        if (!empty($_GET['id'])) {
            $id = $_GET['id'];
            Order::find($id)->delete();
        }
    }

    public function add_order()
    {
        if (!empty($_GET)) {
            $data = $_GET;

            $order = new Order();
            $order->client_id = $_GET['client_id'];
            $order->order_date = $_GET['order_date'];
            $order->order_number = $_GET['order_number'];
            $order->order_total = 0;
            $order->payment = $_GET['payment'];
            $order->withdraw = $_GET['withdraw'];
            $order->complete_order = 0;
            $order->save();

            return $order->id;
        }
    }

    public function add_order_products()
    {
        if (!empty($_GET)) {
            $data = $_GET;

            if ($data['order_number'] != $data['order_old_number']) {
                $validator = Validator::make($data, ['order_number' => 'unique:orders'])->validate();
            }

            $validator = Validator::make(
                $data,
                [
                    "order_date" => ['required'],
                    "order_number" => ['required'],
                    "order_old_number" => ['required'],
                    "total_order" => ['required'],
                    "payment" => ['required'],
                    "withdraw" => ['required'],
                    "prod" => ['required'],
                ]
            )->validate();

            $order_total = str_replace('.', '', $data['total_order']);
            $order_total = str_replace(',', '.', $order_total);

            $order = Order::find($data['order_id']);
            $order->order_date = $data['order_date'];
            $order->order_number = $data['order_number'];
            $order->order_total = $order_total;
            $order->payment = $data['payment'];
            $order->withdraw = $data['withdraw'];
            $order->save();

            Order_product::where('order_id', $data['order_old_number'])->delete();

            foreach ($data['prod'] as $item) {
                if (!empty($item['product_name'])) {

                    $quant = str_replace('.', '', $item['quant']);

                    $unit_price = str_replace('.', '', $item['unit_val']);
                    $unit_price = str_replace(',', '.', $unit_price);

                    $total_price = str_replace('.', '', $item['total_val']);
                    $total_price = str_replace(',', '.', $total_price);

                    $order_prod = new Order_product();
                    $order_prod->order_id = $data['order_number'];
                    $order_prod->product_id = $item['product_name'];
                    $order_prod->quant = $quant;
                    $order_prod->unit_price = $unit_price;
                    $order_prod->total_price = $total_price;
                    $order_prod->delivery_date = $item['delivery_date'];
                    $order_prod->save();
                }
            }

            Helper::saveLog(Auth::user()->id, 'Alteração', $order->id, $order->order_number, 'Pedidos');

            return $data;
        }
    }

    public function order_change_status()
    {
        if (isset($_GET['id']) && isset($_GET['stat'])) {
            $id = $_GET['id'];
            $stat = $_GET['stat'];

            $order_change_status = Order::find($id);
            $order_change_status->complete_order = $stat;
            $order_change_status->save();

            echo 'Ok!';
        } else {
            echo 'Erro na alteração!';
        }
    }
}
