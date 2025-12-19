<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas públicas
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
| Auth::routes() já registra /login, /register, /logout (POST) etc.
*/
Auth::routes();

/*
|--------------------------------------------------------------------------
| Rotas protegidas (auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home
    |--------------------------------------------------------------------------
    */
    Route::get('/home', 'HomeController@index')->name('home');

    /*
    |--------------------------------------------------------------------------
    | CRUDs principais (resources)
    |--------------------------------------------------------------------------
    */
    Route::resource('orders', 'OrderController');
    Route::resource('order_products', 'OrderProductController');
    Route::resource('products', 'ProductController');
    Route::resource('clients', 'ClientController');
    Route::resource('permissions', 'PermissionController');
    Route::resource('users', 'UserController');
    Route::resource('categories', 'CategoryController');
    Route::resource('sellers', 'SellerController');
    Route::resource('permission-items', 'PermissionItemController');

    /*
    |--------------------------------------------------------------------------
    | Estoque por produto (nested)
    |--------------------------------------------------------------------------
    */
    Route::prefix('products/{product}')->group(function () {
        Route::get('stocks', 'ProductStockController@index')->name('products.stocks.index');
        Route::get('stocks/create', 'ProductStockController@create')->name('products.stocks.create');
        Route::post('stocks', 'ProductStockController@store')->name('products.stocks.store');

        Route::get('stocks/{stock}', 'ProductStockController@show')->name('products.stocks.show');
        Route::get('stocks/{stock}/edit', 'ProductStockController@edit')->name('products.stocks.edit');
        Route::put('stocks/{stock}', 'ProductStockController@update')->name('products.stocks.update');
        Route::delete('stocks/{stock}', 'ProductStockController@destroy')->name('products.stocks.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Relatórios
    |--------------------------------------------------------------------------
    | /reports agora deve ser HUB (lista de relatórios)
    | Mantive o relatório antigo em /report/delivery (como você já usa)
    */
    // HUB
    Route::get('reports', 'ReportController@index')->name('reports.index');

    // Form do relatório antigo
    Route::get('reports/delivery', 'ReportController@deliveryForm')->name('reports.delivery_form');

    // Resultado do relatório (o seu método que já existe)
    Route::get('report/delivery', 'ReportController@reportDelivery')->name('report_delivery');

    // Auditoria
    Route::get('reports/stock-audit', 'StockAuditController@index')->name('reports.stock_audit');

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    Route::get('logs', 'LogController@index')->name('logs.index');
    Route::get('log', 'LogController@index')->name('logs'); // alias antigo

    /*
    |--------------------------------------------------------------------------
    | Rotas já existentes (mantidas)
    |--------------------------------------------------------------------------
    */
    Route::get('orders_client', 'OrderController@orders_client')->name('orders_client');
    Route::post('add_line', 'OrderController@add_line')->name('add_line');
    Route::post('edit_line', 'OrderController@edit_line')->name('edit_line');
    Route::delete('order/order_product/{order_product}/destroy', 'OrderController@order_product_destroy')
        ->name('orders.order_product_destroy');

    Route::get('products/cc_products/{id}', 'ProductController@cc_product')->name('cc_product');
    Route::get('clients/cc_clients/{id}', 'ClientController@cc_client')->name('cc_client');
    Route::get('orders/cc_orders/{id}', 'OrderController@cc_order')->name('cc_order');
    Route::get('sellers/cc_sellers/{id}', 'SellerController@cc_seller')->name('cc_seller');

    Route::get('orders/{order}/update-status', 'OrderController@updateStatus')->name('order.update_status');

    Route::get('order_products/{order_product}/delivery', 'OrderProductController@delivery')->name('order_products.delivery');
    Route::post('order_products/{order_product}/delivered', 'OrderProductController@delivered')->name('order_products.delivered');

    Route::get('products/day_delivery_recalc/{id}', 'ProductController@day_delivery_recalc')->name('day_delivery_recalc');

    /*
    |--------------------------------------------------------------------------
    | AJAXCONTROLLERS (mantidos como estão)
    |--------------------------------------------------------------------------
    | Obs.: alguns alteram dados via GET — mantido por compatibilidade.
    */
    Route::get('edit_complete_order', 'AjaxController@edit_complete_order')->name('edit_complete_order');
    Route::get('day_delivery_calc', 'AjaxController@day_delivery_calc')->name('day_delivery_calc');
    Route::get('search', 'AjaxController@search')->name('search');
    Route::get('search_order_number', 'AjaxController@search_order_number')->name('search_order_number');
    Route::get('register_delivery', 'AjaxController@register_delivery')->name('register_delivery');
    Route::get('register_cancel', 'AjaxController@register_cancel')->name('register_cancel');
    Route::get('saldo_produto', 'AjaxController@saldo_produto')->name('saldo_produto');
    Route::get('update_admin', 'AjaxController@update_admin')->name('update_admin');
    Route::get('del_line', 'AjaxController@del_line')->name('del_line');
    Route::get('add_order', 'AjaxController@add_order')->name('add_order');
    Route::get('add_order_products', 'AjaxController@add_order_products')->name('add_order_products');
    Route::get('order_change_status', 'AjaxController@order_change_status')->name('order_change_status');
    Route::get('del_dup_order', 'AjaxController@del_dup_order')->name('del_dup_order');
    Route::get('get_data_product', 'AjaxController@get_data_product')->name('get_data_product');
    Route::post('update-payment-status', 'AjaxController@update_payment_status')->name('update_payment_status');

    /*
    |--------------------------------------------------------------------------
    | Extras
    |--------------------------------------------------------------------------
    */
    Route::post('products/{order_product}/marcar-produto', 'ProductController@marcar_produto')->name('products.marcar_produto');
    Route::get('orders_conclude', 'OrderController@orders_conclude')->name('orders_conclude');

    /*
    |--------------------------------------------------------------------------
    | Impressão
    |--------------------------------------------------------------------------
    */
    Route::get('orders/{order}/print', 'OrderPrintController@show')->name('orders.print');

    /*
    |--------------------------------------------------------------------------
    | Logout por GET (compatibilidade com o layout atual)
    |--------------------------------------------------------------------------
    | Seu layout chama route('logout') via GET.
    | Isso sobrescreve o logout POST do Auth::routes() no uso do helper route().
    */
    Route::get('logout', function () {
        Auth::logout();
        return redirect()->route('home');
    })->name('logout');
});
