<?php

use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Composer;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/../fixtures/stubs/modules/valid/article',
        __DIR__ . '/../fixtures/stubs/modules/valid/author',
        __DIR__ . '/../fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/../fixtures/stubs/modules/valid/empty',
        __DIR__ . '/../fixtures/stubs/modules/valid/navigation',
    ], $this->app->basePath('/modules'));

    /** @var ModulesRepository $modulesRepository */
    $modulesRepository = $this->app[ModulesRepository::class];
    $this->modulesRepository = $modulesRepository;
});

it('deletes a module', function () {
    expect($this->modulesRepository->has('laraneat/article'))->toBe(true);

    $this->instance(Composer::class, $this->mockComposer(['composer', 'remove', 'laraneat/article']));

    $this->artisan('module:delete article')
        ->assertSuccessful();

    expect($this->modulesRepository->has('laraneat/article'))->toBe(false);
});
