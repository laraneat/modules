<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
    ], $this->app->basePath('/modules'));
});

it('generates factory for the module', function () {
    $this->artisan('module:make:factory', [
        'name' => 'SomeAuthorFactory',
        'module' => 'Author',
        '--model' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Factories/SomeAuthorFactory.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
