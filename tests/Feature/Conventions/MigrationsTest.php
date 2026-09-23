<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('adds the migrations of the modules to the migrator', function () {
    expect(app('migrator')->paths())->toBe([$this->path('modules/blog/database/migrations')]);
});

it('runs the migrations of the modules', function () {
    config(['database.default' => 'testing']);

    $this->artisan('migrate')->assertSuccessful();

    expect(Schema::hasTable('posts'))->toBeTrue();

    $this->artisan('migrate:rollback')->assertSuccessful();

    expect(Schema::hasTable('posts'))->toBeFalse();
});
