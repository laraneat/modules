<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates "api" controller for the module', function () {
    $this->artisan('module:make:controller', [
        'name' => 'ApiAuthorController',
        'module' => 'Author',
        '--ui' => 'api',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/UI/API/Controllers/ApiAuthorController.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "web" controller for the module', function () {
    $this->artisan('module:make:controller', [
        'name' => 'WebAuthorController',
        'module' => 'Author',
        '--ui' => 'web',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/UI/Web/Controllers/WebAuthorController.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
