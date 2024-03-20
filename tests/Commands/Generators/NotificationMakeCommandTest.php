<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "plain" notification for the module', function () {
    $this->artisan('module:make:notification', [
        'name' => 'PlainAuthorNotification',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Notifications/PlainAuthorNotification.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "queued" notification for the module', function () {
    $this->artisan('module:make:notification', [
        'name' => 'QueuedAuthorNotification',
        'module' => 'Author',
        '--stub' => 'queued',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Notifications/QueuedAuthorNotification.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
