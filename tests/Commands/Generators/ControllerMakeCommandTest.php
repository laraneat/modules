<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "api" controller for the module', function () {
    $this->artisan('module:make:controller', [
        'name' => 'ApiAuthorController',
        'module' => 'Author',
        '--ui' => 'api',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Controllers/ApiAuthorController.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "web" controller for the module', function () {
    $this->artisan('module:make:controller', [
        'name' => 'WebAuthorController',
        'module' => 'Author',
        '--ui' => 'web',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/Controllers/WebAuthorController.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
