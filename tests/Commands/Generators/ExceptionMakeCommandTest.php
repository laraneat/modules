<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates exception for the module', function () {
    $this->artisan('module:make:exception', [
        'name' => 'SomeAuthorException',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Exceptions/SomeAuthorException.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
