<?php

use Illuminate\Support\Facades\Route;
use Modules\ArticleComment\Actions\CreateArticleCommentAction;

Route::post('article-comments', CreateArticleCommentAction::class)
    ->name('api.article_comments.create');
