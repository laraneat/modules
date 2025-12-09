<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates "get" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'list_authors',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'ListAuthorsAction',
        '--method' => 'get',
        '--url' => 'authors',
        '--name' => 'web.authors.list',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/list_authors.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "post" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'create_author',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'CreateAuthorAction',
        '--method' => 'post',
        '--url' => 'authors',
        '--name' => 'web.authors.create',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/create_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "put" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'update_author',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'UpdateAuthorAction',
        '--method' => 'put',
        '--url' => 'authors/{author}',
        '--name' => 'web.authors.update',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/update_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "patch" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'update_author',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'UpdateAuthorAction',
        '--method' => 'patch',
        '--url' => 'authors/{author}',
        '--name' => 'web.authors.update',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/update_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'delete_author',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'DeleteAuthorAction',
        '--method' => 'delete',
        '--url' => 'authors/{author}',
        '--name' => 'web.authors.delete',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/delete_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "options" web route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'options_authors',
        'module' => 'Author',
        '--ui' => 'web',
        '--action' => 'ListAuthorsAction',
        '--method' => 'options',
        '--url' => 'authors',
        '--name' => 'web.authors.options',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/WEB/routes/options_authors.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "get" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'list_authors',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'ListAuthorsAction',
        '--method' => 'get',
        '--url' => 'authors',
        '--name' => 'web.authors.list',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/list_authors.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "post" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'create_author',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'CreateAuthorAction',
        '--method' => 'post',
        '--url' => 'authors',
        '--name' => 'api.authors.create',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/create_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "put" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'update_author',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'UpdateAuthorAction',
        '--method' => 'put',
        '--url' => 'authors/{author}',
        '--name' => 'api.authors.update',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/update_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "patch" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'update_author',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'UpdateAuthorAction',
        '--method' => 'patch',
        '--url' => 'authors/{author}',
        '--name' => 'api.authors.update',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/update_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'delete_author',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'DeleteAuthorAction',
        '--method' => 'delete',
        '--url' => 'authors/{author}',
        '--name' => 'api.authors.delete',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/delete_author.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "options" api route for the module', function () {
    $this->artisan('module:make:route', [
        'name' => 'options_authors',
        'module' => 'Author',
        '--ui' => 'api',
        '--action' => 'ListAuthorsAction',
        '--method' => 'options',
        '--url' => 'authors',
        '--name' => 'api.authors.options',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/API/routes/options_authors.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
