<?php

use Illuminate\Database\Migrations\Migrator;

it('can return is the vendor', function () {
    expect($this->createModule(['isVendor' => false])->isVendor())->toBe(false);
    expect($this->createModule(['isVendor' => true])->isVendor())->toBe(true);
});

it('can return the package name', function () {
    expect($this->createModule(['packageName' => 'some-vendor/testing-module'])->getPackageName())->toBe('some-vendor/testing-module');
    expect($this->createModule(['packageName' => 'testing-module'])->getPackageName())->toBe('testing-module');
    expect($this->createModule(['packageName' => '  some-vendor/module  '])->getPackageName())->toBe('some-vendor/module');
});

it('can return the name', function () {
    expect($this->createModule(['name' => 'TestingModule'])->getName())->toBe('TestingModule');
    expect($this->createModule(['name' => '  SomeModule  '])->getName())->toBe('SomeModule');
    expect($this->createModule(['name' => '  some-module  '])->getName())->toBe('some-module');

    expect($this->createModule(['packageName' => 'some-vendor/testing-module'])->getName())->toBe('testing-module');
    expect($this->createModule(['packageName' => '  some-vendor/module  '])->getName())->toBe('module');
    expect($this->createModule(['packageName' => 'testing-module'])->getName())->toBe('testing-module');
});

it('can return the studly name', function () {
    expect($this->createModule(['name' => 'TestingModule'])->getStudlyName())->toBe('TestingModule');
    expect($this->createModule(['name' => '  SomeModule  '])->getStudlyName())->toBe('SomeModule');
    expect($this->createModule(['name' => '  some-module  '])->getStudlyName())->toBe('SomeModule');

    expect($this->createModule(['packageName' => 'some-vendor/testing-module'])->getStudlyName())->toBe('TestingModule');
    expect($this->createModule(['packageName' => '  some-vendor/module  '])->getStudlyName())->toBe('Module');
    expect($this->createModule(['packageName' => 'testing-module'])->getStudlyName())->toBe('TestingModule');
});

it('can return the kebab name', function () {
    expect($this->createModule(['name' => 'TestingModule'])->getKebabName())->toBe('testing-module');
    expect($this->createModule(['name' => '  SomeModule  '])->getKebabName())->toBe('some-module');
    expect($this->createModule(['name' => '  some-module  '])->getKebabName())->toBe('some-module');

    expect($this->createModule(['packageName' => 'some-vendor/testing-module'])->getKebabName())->toBe('testing-module');
    expect($this->createModule(['packageName' => '  some-vendor/module  '])->getKebabName())->toBe('module');
    expect($this->createModule(['packageName' => 'testing-module'])->getKebabName())->toBe('testing-module');
});

it('can return the snake name', function () {
    expect($this->createModule(['name' => 'TestingModule'])->getSnakeName())->toBe('testing_module');
    expect($this->createModule(['name' => '  SomeModule  '])->getSnakeName())->toBe('some_module');
    expect($this->createModule(['name' => '  some-module  '])->getSnakeName())->toBe('some_module');

    expect($this->createModule(['packageName' => 'some-vendor/testing-module'])->getSnakeName())->toBe('testing_module');
    expect($this->createModule(['packageName' => '  some-vendor/module  '])->getSnakeName())->toBe('module');
    expect($this->createModule(['packageName' => 'testing-module'])->getSnakeName())->toBe('testing_module');
});

it('can return the path', function () {
    expect($this->createModule([
        'path' => $this->app->basePath('/modules/SomeTestingModule'),
    ])->getPath())->toBe($this->app->basePath('/modules/SomeTestingModule'));
});

it('can return the namespace', function () {
    expect($this->createModule(['namespace' => 'Some\\TestingModule\\'])->getNamespace())
        ->toBe('Some\\TestingModule');
    expect($this->createModule(['namespace' => 'Some\\TestingModule\\\\\\'])->getNamespace())
        ->toBe('Some\\TestingModule');
    expect($this->createModule(['namespace' => '\\\\Some\\TestingModule'])->getNamespace())
        ->toBe('Some\\TestingModule');
    expect($this->createModule(['namespace' => '\\\\Some\\TestingModule\\\\\\'])->getNamespace())
        ->toBe('Some\\TestingModule');
});

it('can return providers', function () {
    expect($this->createModule([
        'providers' => [
            'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
            'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider',
        ],
    ])->getProviders())->toBe([
        'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
        'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider',
    ]);
});

it('can return aliases', function () {
    expect($this->createModule([
        'aliases' => [
            'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
            'some' => 'SomeVendor\\TestingModule\\Facades\\Some',
        ],
    ])->getAliases())->toBe([
        'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
        'some' => 'SomeVendor\\TestingModule\\Facades\\Some',
    ]);
});

it('can make sub path', function () {
    expect($this->createModule(['path' => $this->app->basePath('/modules/SomeTestingModule')])
        ->subPath('resources/views/index.blade.php'))
        ->toBe($this->app->basePath('/modules/SomeTestingModule/resources/views/index.blade.php'));

    expect($this->createModule(['path' => $this->app->basePath('/modules/SomeTestingModule')])
        ->subPath('///resources/views/index.blade.php'))
        ->toBe($this->app->basePath('/modules/SomeTestingModule/resources/views/index.blade.php'));
});

it('can return module migration paths', function () {
    $module = $this->createModule(['path' => $this->app->basePath('/modules/SomeTestingModule')]);

    expect($module->getMigrationPaths())->toBe([]);

    /** @var Migrator|null $migrator */
    $migrator = $this->app['migrator'];
    $migrator->path($this->app->basePath('/modules/AnotherTestingModule/database/migrations'));
    $migrator->path($this->app->basePath('/modules/SomeTestingModule/database/migrations'));
    $migrator->path($this->app->basePath('/migrations'));

    expect($module->getMigrationPaths())->toBe([
        $this->app->basePath('/modules/SomeTestingModule/database/migrations'),
    ]);
});

it('can return module as array', function () {
    expect($this->createModule([
        'isVendor' => false,
        'packageName' => 'some-vendor/testing-module',
        'name' => 'TestingModule',
        'path' => $this->app->basePath('modules/TestingModule'),
        'namespace' => '\\\\\\SomeVendor\\TestingModule\\\\',
        'providers' => [
            'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
            'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
            'some' => 'SomeVendor\\TestingModule\\Facades\\Some',
        ],
    ])->toArray())->toBe([
        'isVendor' => false,
        'packageName' => 'some-vendor/testing-module',
        'name' => 'TestingModule',
        'path' => $this->app->basePath('modules/TestingModule'),
        'namespace' => 'SomeVendor\\TestingModule',
        'providers' => [
            'SomeVendor\\TestingModule\\Providers\\TestingModuleServiceProvider',
            'SomeVendor\\TestingModule\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'testing-module' => 'SomeVendor\\TestingModule\\Facades\\TestingModule',
            'some' => 'SomeVendor\\TestingModule\\Facades\\Some',
        ],
    ]);
});
