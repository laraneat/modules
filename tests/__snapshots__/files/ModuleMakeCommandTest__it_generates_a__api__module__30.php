<?php

use Illuminate\Support\Facades\Route;
use Modules\ArticleComment\Actions\ViewArticleCommentAction;

Route::get('article-comments/{articleComment}', ViewArticleCommentAction::class)
    ->name('api.article_comments.view');
