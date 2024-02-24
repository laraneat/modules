<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates event for the module', function () {
    $this->artisan('module:make:event', [
        'name' => 'SomeAuthorEvent',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Events/SomeAuthorEvent.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
