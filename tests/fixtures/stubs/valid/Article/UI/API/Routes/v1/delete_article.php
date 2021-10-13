<?php

use App\Modules\Article\Actions\DeleteArticleAction;
use Illuminate\Support\Facades\Route;

Route::delete('articles/{article}', DeleteArticleAction::class)
    ->name('api.articles.delete');
