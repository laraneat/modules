<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "eloquent" query-wizard for the module', function () {
    $this->artisan('module:make:query-wizard', [
        'name' => 'AuthorsQueryWizard',
        'module' => 'Author',
        '--stub' => 'eloquent',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/QueryWizards/AuthorsQueryWizard.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "model" query-wizard for the module', function () {
    $this->artisan('module:make:query-wizard', [
        'name' => 'AuthorQueryWizard',
        'module' => 'Author',
        '--stub' => 'model',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/QueryWizards/AuthorQueryWizard.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
