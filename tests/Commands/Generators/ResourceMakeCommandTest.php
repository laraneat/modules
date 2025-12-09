<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "single" resource for the module', function () {
    $this->artisan('module:make:resource', [
        'name' => 'AuthorResource',
        'module' => 'Author',
        '--stub' => 'single',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Resources/AuthorResource.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "collection" resource for the module', function () {
    $this->artisan('module:make:resource', [
        'name' => 'AuthorResourceCollection',
        'module' => 'Author',
        '--stub' => 'collection',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Resources/AuthorResourceCollection.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
