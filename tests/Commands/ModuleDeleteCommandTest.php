<?php

use Laraneat\Modules\ModulesRepository;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Article'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));

    $this->setVendorModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);

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
    expect($this->modulesRepository->has('laraneat/author'))->toBe(true);

    $this->artisan('module:delete article laraneat/author')
        ->assertSuccessful();

    expect($this->modulesRepository->has('laraneat/article'))->toBe(false);
    expect($this->modulesRepository->has('laraneat/author'))->toBe(false);
});
