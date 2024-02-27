<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "unit" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ExampleTest',
        'module' => 'Author',
        '--type' => 'unit'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/Unit/ExampleTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates plain "feature" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ExampleTest',
        'module' => 'Author',
        '--type' => 'feature'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/Feature/ExampleTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates plain "cli" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ExampleTest',
        'module' => 'Author',
        '--type' => 'cli'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/CLI/ExampleTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates plain "web" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ExampleTest',
        'module' => 'Author',
        '--type' => 'web',
        '--stub' => 'plain',
        '--model' => 'Author',
        '--route' => 'web.authors.example'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/WEB/ExampleTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates create "web" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'CreateAuthorTest',
        'module' => 'Author',
        '--type' => 'web',
        '--stub' => 'create',
        '--model' => 'Author',
        '--route' => 'web.authors.create'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/WEB/CreateAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates update "web" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'UpdateAuthorTest',
        'module' => 'Author',
        '--type' => 'web',
        '--stub' => 'update',
        '--model' => 'Author',
        '--route' => 'web.authors.update'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/WEB/UpdateAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates delete "web" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'DeleteAuthorTest',
        'module' => 'Author',
        '--type' => 'web',
        '--stub' => 'delete',
        '--model' => 'Author',
        '--route' => 'web.authors.delete'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/WEB/DeleteAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates plain "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ExampleTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'plain',
        '--model' => 'Author',
        '--route' => 'api.authors.example'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/ExampleTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates create "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'CreateAuthorTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'create',
        '--model' => 'Author',
        '--route' => 'api.authors.create'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/CreateAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates update "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'UpdateAuthorTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'update',
        '--model' => 'Author',
        '--route' => 'api.authors.update'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/UpdateAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates delete "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'DeleteAuthorTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'delete',
        '--model' => 'Author',
        '--route' => 'api.authors.delete'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/DeleteAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates list "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ListAuthorsTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'list',
        '--model' => 'Author',
        '--route' => 'api.authors.list'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/ListAuthorsTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates view "api" test for the module', function () {
    $this->artisan('module:make:test', [
        'name' => 'ViewAuthorTest',
        'module' => 'Author',
        '--type' => 'api',
        '--stub' => 'view',
        '--model' => 'Author',
        '--route' => 'api.authors.view'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/tests/UI/API/ViewAuthorTest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

