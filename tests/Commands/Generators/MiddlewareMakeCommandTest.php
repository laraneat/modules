<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/modules'));
});

it('generates middleware for the module', function () {
    $this->artisan('module:make:middleware', [
        'name' => 'SomeAuthorMiddleware',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Middleware/SomeAuthorMiddleware.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
