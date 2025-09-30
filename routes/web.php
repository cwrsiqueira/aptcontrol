<?php

use App\Http\Controllers\OrderProductController;
use App\Order_product;
use Hamcrest\Number\OrderingComparison;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

//
// Público
//
Route::get('/', function () {
    return view('welcome');
});

// Auth (uma única vez)
Auth::routes();

//
// Requer autenticado
//
Route::middleware('auth')->group(function () {

    Route::get('/home', 'HomeController@index')->name('home');

    // Recursos principais
    Route::resource('/orders',        'OrderController');
    Route::resource('/order_products', 'OrderProductController');
    Route::resource('/products',      'ProductController');
    Route::resource('/clients',       'ClientController');
    Route::resource('/reports',       'ReportController');
    Route::resource('/integrations',  'IntegrationController');
    Route::resource('/permissions',   'PermissionController');
    Route::resource('/users',         'UserController');
    Route::resource('/categories',    'CategoryController');
    Route::resource('/sellers',       'SellerController');

    //
    // Rotas já existentes (mantidas)
    //
    Route::get('/orders_client', 'OrderController@orders_client')->name('orders_client');
    Route::post('/add_line', 'OrderController@add_line')->name('add_line');
    Route::post('/edit_line', 'OrderController@edit_line')->name('edit_line');
    Route::delete('/order/order_product/{order_product}/destroy', 'OrderController@order_product_destroy')->name('orders.order_product_destroy');

    Route::get('/log',  'LogController@index')->name('logs.index');
    Route::get('/logs', 'LogController@index')->name('logs'); // alias

    Route::get('/products/cc_products/{id}', 'ProductController@cc_product')->name('cc_product');
    Route::get('/clients/cc_clients/{id}',   'ClientController@cc_client')->name('cc_client');
    Route::get('/orders/cc_orders/{id}',     'OrderController@cc_order')->name('cc_order');
    Route::get('/sellers/cc_sellers/{id}',   'SellerController@cc_seller')->name('cc_seller');

    Route::get('order_products/{order_product}/delivery', 'OrderProductController@delivery')->name('order_products.delivery');
    Route::post('order_products/{order_product}/delivered', 'OrderProductController@delivered')->name('order_products.delivered');

    Route::get('/products/day_delivery_recalc/{id}', 'ProductController@day_delivery_recalc')->name('day_delivery_recalc');

    //
    // AJAXCONTROLLERS (mantidos como estão — apesar de alguns alterarem dados via GET)
    //
    Route::get('/edit_complete_order',  'AjaxController@edit_complete_order')->name('edit_complete_order');
    Route::get('/day_delivery_calc',    'AjaxController@day_delivery_calc')->name('day_delivery_calc');
    Route::get('/search',               'AjaxController@search')->name('search');
    Route::get('/search_order_number',  'AjaxController@search_order_number')->name('search_order_number');
    Route::get('/register_delivery',    'AjaxController@register_delivery')->name('register_delivery');
    Route::get('/register_cancel',      'AjaxController@register_cancel')->name('register_cancel');
    Route::get('/saldo_produto',        'AjaxController@saldo_produto')->name('saldo_produto');
    Route::get('/update_admin',         'AjaxController@update_admin')->name('update_admin');
    Route::get('/del_line',             'AjaxController@del_line')->name('del_line');
    Route::get('/add_order',            'AjaxController@add_order')->name('add_order');
    Route::get('/add_order_products',   'AjaxController@add_order_products')->name('add_order_products');
    Route::get('/order_change_status',  'AjaxController@order_change_status')->name('order_change_status');
    Route::get('/del_dup_order',        'AjaxController@del_dup_order')->name('del_dup_order');
    Route::get('/get_data_product',     'AjaxController@get_data_product')->name('get_data_product');

    // Toggle favorito de cliente
    Route::post('/clients/{client}/toggle-favorite', 'ClientController@toggleFavorite')
        ->name('clients.toggle_favorite');
    // Toggle favorito da data do pedido (order_date)
    Route::post('/orders/{order}/toggle-date-favorite', 'OrderController@toggleDateFavorite')
        ->name('orders.toggle_date_favorite');
    // Toggle favorito da DATA DE ENTREGA por item (order_products)
    Route::post('/order-products/{orderProduct}/toggle-delivery-favorite', 'OrderController@toggleDeliveryFavorite')
        ->name('order_products.toggle_delivery_favorite');

    // Relatórios adicionais e concluídos
    Route::get('/report_delivery',           'ReportController@report_delivery')->name('report_delivery');
    Route::get('/report_delivery_byPeriod',  'ReportController@report_delivery_byPeriod')->name('report_delivery_byPeriod');
    Route::get('/orders_conclude',           'OrderController@orders_conclude')->name('orders_conclude');

    // Logout por GET (mantido para compatibilidade com seu layout)
    Route::get('/logout', function () {
        Auth::logout();
        return redirect()->route('home');
    })->name('logout.get');
});
