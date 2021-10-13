<?php

use App\Modules\Article\Actions\CreateArticleAction;
use Illuminate\Support\Facades\Route;

Route::post('articles', CreateArticleAction::class)
    ->name('api.articles.create');
