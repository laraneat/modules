<?php

use Laraneat\Modules\ModulesRepository;

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

it('deletes one module', function () {
    expect($this->modulesRepository->has('laraneat/article'))->toBe(true);

    $this->artisan('module:delete article')
        ->assertSuccessful();

    expect($this->modulesRepository->has('laraneat/article'))->toBe(false);
});

it('deletes multiple module', function () {
    expect($this->modulesRepository->has('laraneat/article'))->toBe(true);
    expect($this->modulesRepository->has('laraneat/empty'))->toBe(true);

    $this->artisan('module:delete article laraneat/empty')
        ->assertSuccessful();

    expect($this->modulesRepository->has('laraneat/article'))->toBe(false);
    expect($this->modulesRepository->has('laraneat/empty'))->toBe(false);
});
