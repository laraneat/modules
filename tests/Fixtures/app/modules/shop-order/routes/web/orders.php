<?php

use Illuminate\Support\Facades\Route;

Route::get('orders', fn () => 'orders')->name('shop-order.orders');
