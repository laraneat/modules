<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\DeleteAuthorAction;

Route::delete('authors/{author}', DeleteAuthorAction::class)
    ->name('api.authors.delete');
