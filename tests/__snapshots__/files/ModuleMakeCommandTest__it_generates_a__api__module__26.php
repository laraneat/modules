<?php

use Illuminate\Support\Facades\Route;
use Modules\ArticleComment\Actions\DeleteArticleCommentAction;

Route::delete('article-comments/{articleComment}', DeleteArticleCommentAction::class)
    ->name('api.article_comments.delete');
