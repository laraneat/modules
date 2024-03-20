<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates plain policy for the module', function () {
    $this->artisan('module:make:policy', [
        'name' => 'AuthorPolicy',
        'module' => 'Author',
    ])
        ->expectsQuestion('Enter the class name of the model to be used in the policy (optional)', '')
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Policies/AuthorPolicy.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates full policy for the module', function () {
    $this->artisan('module:make:policy', [
        'name' => 'AuthorPolicy',
        'module' => 'Author',
        '--model' => 'Author',
    ])
        ->expectsQuestion(
            'Enter the class name of the "User model"',
            'Modules\\User\\Models\\User'
        )
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Policies/AuthorPolicy.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
