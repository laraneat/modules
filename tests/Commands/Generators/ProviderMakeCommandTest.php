<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "plain" provider for the module', function () {
    $this->artisan('module:make:provider', [
        'name' => 'TestServiceProvider',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Providers/TestServiceProvider.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "module" provider for the module', function () {
    $this->artisan('module:make:provider', [
        'name' => 'CustomModuleServiceProvider',
        'module' => 'Author',
        '--stub' => 'module',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Providers/CustomModuleServiceProvider.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "event" provider for the module', function () {
    $this->artisan('module:make:provider', [
        'name' => 'EventServiceProvider',
        'module' => 'Author',
        '--stub' => 'event',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Providers/EventServiceProvider.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "route" provider for the module', function () {
    $this->artisan('module:make:provider', [
        'name' => 'CustomRouteServiceProvider',
        'module' => 'Author',
        '--stub' => 'route',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Providers/CustomRouteServiceProvider.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('registers provider in module composer.json', function () {
    $this->artisan('module:make:provider', [
        'name' => 'CustomServiceProvider',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $composerJsonPath = $this->app->basePath('/modules/author/composer.json');
    $composerJson = json_decode(file_get_contents($composerJsonPath), true);

    expect($composerJson['extra']['laravel']['providers'])
        ->toContain('Modules\\Author\\Providers\\CustomServiceProvider');
});
