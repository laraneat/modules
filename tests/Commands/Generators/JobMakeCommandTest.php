<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates "plain" job for the module', function () {
    $this->artisan('module:make:job', [
        'name' => 'PlainAuthorJob',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Jobs/PlainAuthorJob.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "queued" job for the module', function () {
    $this->artisan('module:make:job', [
        'name' => 'QueuedAuthorJob',
        'module' => 'Author',
        '--stub' => 'queued',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Jobs/QueuedAuthorJob.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
