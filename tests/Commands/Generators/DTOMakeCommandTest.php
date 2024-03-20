<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates dto for the module', function () {
    $this->artisan('module:make:dto', [
        'name' => 'SomeAuthorDTO',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/DTO/SomeAuthorDTO.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
