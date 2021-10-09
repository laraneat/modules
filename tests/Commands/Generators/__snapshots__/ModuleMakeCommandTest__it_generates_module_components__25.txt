<?php

use App\Modules\Article\Actions\ListArticlesAction;
use Illuminate\Support\Facades\Route;

Route::get('articles', ListArticlesAction::class)
    ->name('api.articles.list');
