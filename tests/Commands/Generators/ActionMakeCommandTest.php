<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Article'),
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));

    $this->setVendorModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);
});

it('generates "plain" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'PlainArticleAction',
        'module' => 'Article',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/PlainArticleAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'CreateArticleAction',
        'module' => 'Article',
        '--stub' => 'create',
        '--dto' => 'App\Modules\Article\DTO\CreateArticleDTO',
        '--model' => 'App\Modules\Article\Models\Article',
        '--request' => 'App\Modules\Article\UI\API\Requests\CreateArticleRequest',
        '--resource' => 'App\Modules\Article\UI\API\Resources\ArticleResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/CreateArticleAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "update" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'UpdateArticleAction',
        'module' => 'Article',
        '--stub' => 'update',
        '--dto' => 'App\Modules\Article\DTO\UpdateArticleDTO',
        '--model' => 'App\Modules\Article\Models\Article',
        '--request' => 'App\Modules\Article\UI\API\Requests\UpdateArticleRequest',
        '--resource' => 'App\Modules\Article\UI\API\Resources\ArticleResource',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/UpdateArticleAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'DeleteArticleAction',
        'module' => 'Article',
        '--stub' => 'delete',
        '--model' => 'App\Modules\Article\Models\Article',
        '--request' => 'App\Modules\Article\UI\API\Requests\DeleteArticleRequest',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/DeleteArticleAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "view" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ViewArticleAction',
        'module' => 'Article',
        '--stub' => 'view',
        '--model' => 'App\Modules\Article\Models\Article',
        '--request' => 'App\Modules\Article\UI\API\Requests\ViewArticleRequest',
        '--resource' => 'App\Modules\Article\UI\API\Resources\ArticleResource',
        '--wizard' => 'App\Modules\Article\UI\API\QueryWizards\ArticleQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/ViewArticleAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

it('generates "list" action for the module', function () {
    $this->artisan('module:make:action', [
        'name' => 'ListArticlesAction',
        'module' => 'Article',
        '--stub' => 'list',
        '--model' => 'App\Modules\Article\Models\Article',
        '--request' => 'App\Modules\Article\UI\API\Requests\ListArticlesRequest',
        '--resource' => 'App\Modules\Article\UI\API\Resources\ArticleResource',
        '--wizard' => 'App\Modules\Article\UI\API\QueryWizards\ArticlesQueryWizard',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Article/Actions/ListArticlesAction.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});

