<?php

use Illuminate\Support\Facades\Route;

Route::get('blog', fn () => view('blog::index', ['title' => __('blog::messages.welcome')]));
