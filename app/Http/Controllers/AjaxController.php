<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\Client;
use App\Product;

class AjaxController extends Controller
{
    public function edit_payment() {
        if (!empty($_GET['payment'])) {
            $payment = $_GET['payment'];
            $id = $_GET['id'] ;

            $order = Order::find($id);
            $order->payment = $payment;
            $order->save();

            echo json_encode('Alterado com sucesso para pagamento '.$payment.'!');
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
}
