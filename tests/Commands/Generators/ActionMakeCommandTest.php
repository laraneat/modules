<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates "plain" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'PlainAuthorAction',
        'module' => 'Author',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/PlainAuthorAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'CreateAuthorAction',
        'module' => 'Author',
        '--stub' => 'create',
        '--dto' => 'App\Modules\Author\DTO\CreateAuthorDTO',
        '--model' => 'App\Modules\Author\Models\Author',
        '--request' => 'App\Modules\Author\UI\API\Requests\CreateAuthorRequest',
        '--resource' => 'App\Modules\Author\UI\API\Resources\AuthorResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/CreateAuthorAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "update" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'UpdateAuthorAction',
        'module' => 'Author',
        '--stub' => 'update',
        '--dto' => 'App\Modules\Author\DTO\UpdateAuthorDTO',
        '--model' => 'App\Modules\Author\Models\Author',
        '--request' => 'App\Modules\Author\UI\API\Requests\UpdateAuthorRequest',
        '--resource' => 'App\Modules\Author\UI\API\Resources\AuthorResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/UpdateAuthorAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'DeleteAuthorAction',
        'module' => 'Author',
        '--stub' => 'delete',
        '--model' => 'App\Modules\Author\Models\Author',
        '--request' => 'App\Modules\Author\UI\API\Requests\DeleteAuthorRequest',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/DeleteAuthorAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "view" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ViewAuthorAction',
        'module' => 'Author',
        '--stub' => 'view',
        '--model' => 'App\Modules\Author\Models\Author',
        '--request' => 'App\Modules\Author\UI\API\Requests\ViewAuthorRequest',
        '--resource' => 'App\Modules\Author\UI\API\Resources\AuthorResource',
        '--wizard' => 'App\Modules\Author\UI\API\QueryWizards\AuthorQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/ViewAuthorAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "list" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ListAuthorsAction',
        'module' => 'Author',
        '--stub' => 'list',
        '--model' => 'App\Modules\Author\Models\Author',
        '--request' => 'App\Modules\Author\UI\API\Requests\ListAuthorsRequest',
        '--resource' => 'App\Modules\Author\UI\API\Resources\AuthorResource',
        '--wizard' => 'App\Modules\Author\UI\API\QueryWizards\AuthorsQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Actions/ListAuthorsAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
