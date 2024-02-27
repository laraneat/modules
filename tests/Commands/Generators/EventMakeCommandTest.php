<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
    ], $this->app->basePath('/modules'));
});

it('generates event for the module', function () {
    $this->artisan('module:make:event', [
        'name' => 'SomeAuthorEvent',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Events/SomeAuthorEvent.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
