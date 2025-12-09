<?php

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('shows migration status for the specified module', function () {
    $this->artisan('module:migrate:status', [
        'module' => 'Author',
    ])
        ->assertSuccessful();
});

it('does not require confirmation in production', function () {
    $this->app['env'] = 'production';

    // migrate:status should not require confirmation as it's read-only
    $this->artisan('module:migrate:status', [
        'module' => 'Author',
    ])
        ->assertSuccessful();
});
