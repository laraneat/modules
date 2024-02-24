<?php

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Article'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
    $this->setVendorModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);
});

it('outputs a table of app modules', function () {
    $this->artisan('module:list --app')
        ->expectsTable(['Package Name', 'Namespace', 'Path'], [
            ['laraneat/article', 'App\\Modules\\Article', $this->app->basePath('/app/Modules/Article')],
            ['laraneat/author', 'App\\Modules\\Author', $this->app->basePath('/app/Modules/Author')],
        ])
        ->assertSuccessful();
});

it('outputs a table of vendor modules', function () {
    $this->artisan('module:list --vendor')
        ->expectsTable(['Package Name', 'Namespace', 'Path'], [
            ['laraneat/foo', 'Laraneat\\Foo', $this->app->basePath('/vendor/laraneat/foo')],
            ['laraneat/bar', 'Laraneat\\Bar', $this->app->basePath('/vendor/laraneat/bar')],
        ])
        ->assertSuccessful();
});

it('outputs a table of all modules', function () {
    $this->artisan('module:list')
        ->expectsTable(['Package Name', 'Namespace', 'Path'], [
            ['laraneat/foo', 'Laraneat\\Foo', $this->app->basePath('/vendor/laraneat/foo')],
            ['laraneat/bar', 'Laraneat\\Bar', $this->app->basePath('/vendor/laraneat/bar')],
            ['laraneat/article', 'App\\Modules\\Article', $this->app->basePath('/app/Modules/Article')],
            ['laraneat/author', 'App\\Modules\\Author', $this->app->basePath('/app/Modules/Author')],
        ])
        ->assertSuccessful();
});
