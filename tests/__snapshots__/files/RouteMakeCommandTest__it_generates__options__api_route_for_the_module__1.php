<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\ListAuthorsAction;

Route::options('authors', ListAuthorsAction::class)
    ->name('api.authors.options');