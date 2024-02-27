<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
    ], $this->app->basePath('/modules'));
});

it('generates "plain" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'PlainAuthorAction',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/PlainAuthorAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'CreateAuthorAction',
        'module' => 'Author',
        '--stub' => 'create',
        '--dto' => 'CreateAuthorDTO',
        '--model' => 'Author',
        '--request' => 'CreateAuthorRequest',
        '--resource' => 'AuthorResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/CreateAuthorAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "update" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'UpdateAuthorAction',
        'module' => 'Author',
        '--stub' => 'update',
        '--dto' => 'UpdateAuthorDTO',
        '--model' => 'Author',
        '--request' => 'UpdateAuthorRequest',
        '--resource' => 'AuthorResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/UpdateAuthorAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'DeleteAuthorAction',
        'module' => 'Author',
        '--stub' => 'delete',
        '--model' => 'Author',
        '--request' => 'DeleteAuthorRequest',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/DeleteAuthorAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "view" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ViewAuthorAction',
        'module' => 'Author',
        '--stub' => 'view',
        '--model' => 'Author',
        '--request' => 'ViewAuthorRequest',
        '--resource' => 'AuthorResource',
        '--wizard' => 'AuthorQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/ViewAuthorAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "list" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ListAuthorsAction',
        'module' => 'Author',
        '--stub' => 'list',
        '--model' => 'Author',
        '--request' => 'ListAuthorsRequest',
        '--resource' => 'AuthorResource',
        '--wizard' => 'AuthorsQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Actions/ListAuthorsAction.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
