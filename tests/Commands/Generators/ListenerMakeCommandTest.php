<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/modules'));
});

it('generates "plain" listener for the module', function () {
    $this->artisan('module:make:listener', [
        'name' => 'PlainAuthorListener',
        'module' => 'Author',
        '--stub' => 'plain',
        '--event' => 'SomeAuthorEvent',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Listeners/PlainAuthorListener.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "queued" listener for the module', function () {
    $this->artisan('module:make:listener', [
        'name' => 'QueuedAuthorListener',
        'module' => 'Author',
        '--stub' => 'queued',
        '--event' => 'SomeAuthorEvent',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Listeners/QueuedAuthorListener.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
