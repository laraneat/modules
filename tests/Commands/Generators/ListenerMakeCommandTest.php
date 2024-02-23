<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates "plain" listener for the module', function () {
    $this->artisan('module:make:listener', [
        'name' => 'PlainAuthorListener',
        'module' => 'Author',
        '--stub' => 'plain',
        '--event' => 'SomeAuthorEvent'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Listeners/PlainAuthorListener.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "queued" listener for the module', function () {
    $this->artisan('module:make:listener', [
        'name' => 'QueuedAuthorListener',
        'module' => 'Author',
        '--stub' => 'queued',
        '--event' => 'SomeAuthorEvent'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Listeners/QueuedAuthorListener.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
