<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\ListAuthorsAction;

Route::get('authors', ListAuthorsAction::class)
    ->name('web.authors.list');
