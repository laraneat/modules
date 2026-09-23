<?php

use Illuminate\Support\Facades\Route;

Route::get('posts', fn () => 'blog posts')->name('blog.posts');
