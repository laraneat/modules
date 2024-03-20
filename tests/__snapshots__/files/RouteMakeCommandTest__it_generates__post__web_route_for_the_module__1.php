<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Actions\CreateAuthorAction;

Route::post('authors', CreateAuthorAction::class)
    ->name('web.authors.create');
