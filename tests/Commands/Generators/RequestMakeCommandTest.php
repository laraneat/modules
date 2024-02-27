<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "plain" web request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'PlainAuthorRequest',
        'module' => 'Author',
        '--stub' => 'plain',
        '--ui' => 'web'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/Requests/PlainAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" web request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'CreateAuthorRequest',
        'module' => 'Author',
        '--stub' => 'create',
        '--ui' => 'web',
        '--dto' => 'CreateAuthorDTO',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/Requests/CreateAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "update" web request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'UpdateAuthorRequest',
        'module' => 'Author',
        '--stub' => 'update',
        '--ui' => 'web',
        '--dto' => 'UpdateAuthorDTO',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/Requests/UpdateAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" web request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'DeleteAuthorRequest',
        'module' => 'Author',
        '--stub' => 'delete',
        '--ui' => 'web',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/Requests/DeleteAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "plain" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'PlainAuthorRequest',
        'module' => 'Author',
        '--stub' => 'plain',
        '--ui' => 'api'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/PlainAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'CreateAuthorRequest',
        'module' => 'Author',
        '--stub' => 'create',
        '--ui' => 'api',
        '--dto' => 'CreateAuthorDTO',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/CreateAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "update" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'UpdateAuthorRequest',
        'module' => 'Author',
        '--stub' => 'update',
        '--ui' => 'api',
        '--dto' => 'UpdateAuthorDTO',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/UpdateAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'DeleteAuthorRequest',
        'module' => 'Author',
        '--stub' => 'delete',
        '--ui' => 'api',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/DeleteAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "view" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'ViewAuthorRequest',
        'module' => 'Author',
        '--stub' => 'view',
        '--ui' => 'api',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/ViewAuthorRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "list" api request for the module', function () {
    $this->artisan('module:make:request', [
        'name' => 'ListAuthorsRequest',
        'module' => 'Author',
        '--stub' => 'list',
        '--ui' => 'api',
        '--model' => 'Author'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/Requests/ListAuthorsRequest.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
