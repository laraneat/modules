<?php

use Laraneat\Modules\Support\ModuleConfigWriter;

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

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

it('can make sub namespace', function () {
    expect($this->createModule(['namespace' => 'SomeVendor\\SomeTestingModule'])
        ->subNamespace('Models\\SomeModel'))
        ->toBe('SomeVendor\\SomeTestingModule\\Models\\SomeModel');

    expect($this->createModule(['namespace' => 'SomeVendor\\SomeTestingModule'])
        ->subNamespace('\\\\Models\\SomeModel\\\\'))
        ->toBe('SomeVendor\\SomeTestingModule\\Models\\SomeModel');
});

it('can return module as array', function () {
    expect($this->createModule([
        'path' => $this->app->basePath('modules/TestingModule'),
        'packageName' => 'some-vendor/testing-module',
        'name' => 'TestingModule',
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
        'path' => $this->app->basePath('modules/TestingModule'),
        'packageName' => 'some-vendor/testing-module',
        'name' => 'TestingModule',
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

it('can update providers via ModuleConfigWriter', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/author',
    ]);

    $module = $this->createModule([
        'path' => $this->app->basePath('/modules/author'),
        'packageName' => 'laraneat/author',
        'name' => 'author',
        'namespace' => 'Modules\\Author',
        'providers' => [
            'Modules\\Author\\Providers\\AuthorServiceProvider',
            'Modules\\Author\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
        ],
    ]);

    $composerJsonPath = $module->subPath('composer.json');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);

    /** @var ModuleConfigWriter $configWriter */
    $configWriter = $this->app->make(ModuleConfigWriter::class);
    $configWriter->updateProviders($module, [
        'Modules\\Author\\Providers\\FooServiceProvider',
        'Modules\\Foo\\Providers\\BarServiceProvider',
    ]);
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
});

it('can update aliases via ModuleConfigWriter', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/author',
    ]);

    $module = $this->createModule([
        'path' => $this->app->basePath('/modules/author'),
        'packageName' => 'laraneat/author',
        'name' => 'author',
        'namespace' => 'Modules\\Author',
        'providers' => [
            'Modules\\Author\\Providers\\AuthorServiceProvider',
            'Modules\\Author\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
        ],
    ]);

    $composerJsonPath = $module->subPath('composer.json');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);

    /** @var ModuleConfigWriter $configWriter */
    $configWriter = $this->app->make(ModuleConfigWriter::class);
    $configWriter->updateAliases($module, [
        'foo' => 'Modules\\Author\\Services\\Foo',
        'bar' => 'Modules\\Bar\\Services\\Bar',
    ]);
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
});

it('can add providers via ModuleConfigWriter', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/author',
    ]);

    $module = $this->createModule([
        'path' => $this->app->basePath('/modules/author'),
        'packageName' => 'laraneat/author',
        'name' => 'author',
        'namespace' => 'Modules\\Author',
        'providers' => [
            'Modules\\Author\\Providers\\AuthorServiceProvider',
            'Modules\\Author\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
        ],
    ]);

    $composerJsonPath = $module->subPath('composer.json');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);

    /** @var ModuleConfigWriter $configWriter */
    $configWriter = $this->app->make(ModuleConfigWriter::class);
    $configWriter->addProvider($module, 'Modules\\Author\\Providers\\FooServiceProvider');
    $configWriter->addProvider($module, 'Modules\\Author\\Providers\\RouteServiceProvider'); // duplicate, should not be added
    $configWriter->addProvider($module, 'Modules\\Foo\\Providers\\BarServiceProvider');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
});

it('can add aliases via ModuleConfigWriter', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/author',
    ]);

    $module = $this->createModule([
        'path' => $this->app->basePath('/modules/author'),
        'packageName' => 'laraneat/author',
        'name' => 'author',
        'namespace' => 'Modules\\Author',
        'providers' => [
            'Modules\\Author\\Providers\\AuthorServiceProvider',
            'Modules\\Author\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [
            'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
        ],
    ]);

    $composerJsonPath = $module->subPath('composer.json');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);

    /** @var ModuleConfigWriter $configWriter */
    $configWriter = $this->app->make(ModuleConfigWriter::class);
    $configWriter->addAlias($module, 'foo', 'Modules\\Author\\Services\\Foo');
    $configWriter->addAlias($module, 'bar', 'Modules\\Bar\\Services\\Bar');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
});
