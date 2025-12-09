<?php

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('resets migrations for the specified module', function () {
    $this->artisan('module:migrate:reset', [
        'module' => 'Author',
        '--pretend' => true,
    ])
        ->assertSuccessful();
});

it('requires confirmation in production', function () {
    $this->app['env'] = 'production';

    $this->artisan('module:migrate:reset', [
        'module' => 'Author',
        '--pretend' => true,
    ])
        ->expectsConfirmation('Are you sure you want to run this command?', 'no')
        ->assertFailed();
});

it('can be forced in production', function () {
    $this->app['env'] = 'production';

    $this->artisan('module:migrate:reset', [
        'module' => 'Author',
        '--pretend' => true,
        '--force' => true,
    ])
        ->assertSuccessful();
});
