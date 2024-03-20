<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\UpdateAuthorAction;

Route::patch('authors/{author}', UpdateAuthorAction::class)
    ->name('api.authors.update');
