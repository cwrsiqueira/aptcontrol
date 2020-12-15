<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Auth::routes();

Route::resource('/orders', 'OrderController');
Route::resource('/products', 'ProductController');
Route::resource('/clients', 'ClientController');
Route::resource('/reports', 'ReportController');
Route::resource('/integrations', 'IntegrationController');
Route::resource('/permissions', 'PermissionController');
Route::resource('/users', 'UserController');
Route::resource('/categories', 'CategoryController');

Route::get('/orders_client', 'OrderController@orders_client')->name('orders_client');
Route::get('/edit_line', 'OrderController@edit_line')->name('edit_line');

Route::post('/add_line', 'OrderController@add_line')->name('add_line');

Route::get('/log', 'LogController@index')->name('logs.index');

Route::get('/products/cc_products/{id}', 'ProductController@cc_product')->name('cc_product');
Route::get('/clients/cc_clients/{id}', 'ClientController@cc_client')->name('cc_client');
Route::get('/products/day_delivery_recalc/{id}', 'ProductController@day_delivery_recalc')->name('day_delivery_recalc');

Route::get('/logs', 'LogController@index')->name('logs');

// AJAXCONTROLLERS
Route::get('/edit_complete_order', 'AjaxController@edit_complete_order')->name('edit_complete_order');
Route::get('/day_delivery_calc', 'AjaxController@day_delivery_calc')->name('day_delivery_calc');
Route::get('/search', 'AjaxController@search')->name('search');
Route::get('/search_order_number', 'AjaxController@search_order_number')->name('search_order_number');
Route::get('/register_delivery', 'AjaxController@register_delivery')->name('register_delivery');
Route::get('/register_cancel', 'AjaxController@register_cancel')->name('register_cancel');
Route::get('/saldo_produto', 'AjaxController@saldo_produto')->name('saldo_produto');
Route::get('/update_admin', 'AjaxController@update_admin')->name('update_admin');
Route::get('/del_line', 'AjaxController@del_line')->name('del_line');
Route::get('/add_order', 'AjaxController@add_order')->name('add_order');
Route::get('/add_order_products', 'AjaxController@add_order_products')->name('add_order_products');
//

Route::get('/report_delivery', 'ReportController@report_delivery')->name('report_delivery');
Route::get('/report_delivery_byPeriod', 'ReportController@report_delivery_byPeriod')->name('report_delivery_byPeriod');
Route::get('/orders_conclude', 'OrderController@orders_conclude')->name('orders_conclude');

Route::get('/logout', function(){
    Auth::logout();
    return redirect()->route('home');
});
