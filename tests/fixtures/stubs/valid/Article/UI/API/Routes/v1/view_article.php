<?php

use App\Modules\Article\Actions\ViewArticleAction;
use Illuminate\Support\Facades\Route;

Route::get('articles/{article}', ViewArticleAction::class)
    ->name('api.articles.view');
