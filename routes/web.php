<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'HomeController@home')->name('home');
Route::get('/cart', 'HomeController@cart')->name('cart');
Route::get('/delivery', 'DeliveryController@delivery')->name('delivery');
Route::post('/delivery/info', 'DeliveryController@storeDeliveryInfo')->name('delivery.store');
Route::get('/promos', 'HomeController@promos')->name('promos');
Route::get('/checkout', 'HomeController@checkout')->name('checkout');
Route::get('/success', function () {
    return view('pages.success');
})->name('success');

Auth::routes();

Route::get('/user', 'UserController@showProfile')->name('user.profile.show')->middleware('auth');
Route::patch('/user/{user}', 'UserController@updateProfile')->name('user.profile.update')->middleware('auth');
Route::patch('/user/{user}/email', 'UserController@verifyEmail')->name('user.email.verify')->middleware('auth');

Route::post('orders', 'OrderController@store')->name('orders.store');
Route::resource('orders', 'OrderController')->only(['index', 'show'])->middleware('auth');

