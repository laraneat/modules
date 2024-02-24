<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates middleware for the module', function () {
    $this->artisan('module:make:middleware', [
        'name' => 'SomeAuthorMiddleware',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Middleware/SomeAuthorMiddleware.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
