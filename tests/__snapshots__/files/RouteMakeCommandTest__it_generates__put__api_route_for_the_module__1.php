<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\UpdateAuthorAction;

Route::put('authors/{author}', UpdateAuthorAction::class)
    ->name('api.authors.update');
