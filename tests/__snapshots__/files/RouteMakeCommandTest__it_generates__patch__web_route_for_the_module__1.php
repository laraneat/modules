<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\UpdateAuthorAction;

Route::patch('authors/{author}', UpdateAuthorAction::class)
    ->name('web.authors.update');
