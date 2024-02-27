<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates observer for the module', function () {
    $this->artisan('module:make:observer', [
        'name' => 'SomeAuthorObserver',
        'module' => 'Author',
        '--model' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Observers/SomeAuthorObserver.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
