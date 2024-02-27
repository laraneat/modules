<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "plain" seeder for the module', function () {
    $this->artisan('module:make:seeder', [
        'name' => 'AuthorsSeeder',
        'module' => 'Author',
        '--stub' => 'plain'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/database/seeders/AuthorsSeeder.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "permissions" seeder for the module', function () {
    $this->artisan('module:make:seeder', [
        'name' => 'AuthorPermissionsSeeder',
        'module' => 'Author',
        '--stub' => 'permissions',
        '--model' => 'Author',
    ])
        ->expectsQuestion(
            'Enter the class name of the "Create permission action"',
            'Modules\\Authorization\\Actions\\CreatePermissionAction'
        )
        ->expectsQuestion(
            'Enter the class name of the "Create permission DTO"',
            'Modules\\Authorization\\DTO\\CreatePermissionDTO'
        )
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/database/seeders/AuthorPermissionsSeeder.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
