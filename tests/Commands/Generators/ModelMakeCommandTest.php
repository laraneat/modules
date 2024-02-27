<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
    ], $this->app->basePath('/modules'));
});

it('generates plain model for the module', function () {
    $this->artisan('module:make:model', [
        'name' => 'AuthorEmail',
        'module' => 'Author',
    ])
        ->expectsQuestion('Enter the class name of the factory to be used in the model (optional)', '')
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Models/AuthorEmail.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates model with factory for the module', function () {
    $this->artisan('module:make:model', [
        'name' => 'AuthorEmail',
        'module' => 'Author',
        '--factory' => 'AuthorEmailFactory',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Models/AuthorEmail.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
