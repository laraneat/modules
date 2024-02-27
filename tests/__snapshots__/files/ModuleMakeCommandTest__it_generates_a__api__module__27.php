<?php

use Illuminate\Support\Facades\Route;
use Modules\ArticleComment\Actions\ListArticleCommentsAction;

Route::get('article-comments', ListArticleCommentsAction::class)
    ->name('api.article_comments.list');
