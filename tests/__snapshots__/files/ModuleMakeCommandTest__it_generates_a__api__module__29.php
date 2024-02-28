<?php

use Illuminate\Support\Facades\Route;
use Modules\ArticleComment\Actions\UpdateArticleCommentAction;

Route::patch('article-comments/{articleComment}', UpdateArticleCommentAction::class)
    ->name('api.article_comments.update');
