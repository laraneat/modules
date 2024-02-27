<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates middleware for the module', function () {
    $this->artisan('module:make:middleware', [
        'name' => 'SomeAuthorMiddleware',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Middleware/SomeAuthorMiddleware.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
