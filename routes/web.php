<?php

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
//    $svc = new \App\Services\AlphaVantageService();
//    return $svc->globalQuote('F17.SI')->getBody();
//    $svc = new \App\Services\WTDService();
//    return $svc->getStockQuote(['D05.SI'])->getBody();
});
