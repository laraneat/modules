<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/modules'));
});

it('generates "plain" job for the module', function () {
    $this->artisan('module:make:job', [
        'name' => 'PlainAuthorJob',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Jobs/PlainAuthorJob.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "queued" job for the module', function () {
    $this->artisan('module:make:job', [
        'name' => 'QueuedAuthorJob',
        'module' => 'Author',
        '--stub' => 'queued',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Jobs/QueuedAuthorJob.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
