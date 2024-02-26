<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/modules'));
});

it('generates event for the module', function () {
    $this->artisan('module:make:event', [
        'name' => 'SomeAuthorEvent',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Events/SomeAuthorEvent.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
