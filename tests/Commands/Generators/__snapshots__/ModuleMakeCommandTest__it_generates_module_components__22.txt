<?php

use App\Modules\Article\Actions\UpdateArticleAction;
use Illuminate\Support\Facades\Route;

Route::patch('articles/{article}', UpdateArticleAction::class)
    ->name('api.articles.update');
