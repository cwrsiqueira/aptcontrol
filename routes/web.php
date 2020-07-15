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

Route::get('/products/cc_products/{id}', 'ProductController@cc_product')->name('cc_product');
Route::get('/clients/cc_clients/{id}', 'ClientController@cc_client')->name('cc_client');

Route::get('/edit_payment', 'AjaxController@edit_payment')->name('edit_payment');
Route::get('/edit_withdraw', 'AjaxController@edit_withdraw')->name('edit_withdraw');
Route::get('/edit_complete_order', 'AjaxController@edit_complete_order')->name('edit_complete_order');
Route::get('/day_delivery_calc', 'AjaxController@day_delivery_calc')->name('day_delivery_calc');
Route::get('/search', 'AjaxController@search')->name('search');

Route::get('/report_delivery', 'ReportController@report_delivery')->name('report_delivery');

Route::get('/logout', function(){
    Auth::logout();
    return redirect()->route('home');
});
