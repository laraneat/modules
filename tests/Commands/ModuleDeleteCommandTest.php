<?php

use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Composer;

beforeEach(function () {
    // Set mock BEFORE anything else so Module instances get the mock
    $this->instance(Composer::class, $this->mockComposer(['removePackages' => true]));

    // Rebind ModulesRepository with the mocked Composer
    $this->app->singleton(ModulesRepository::class, function ($app) {
        return new ModulesRepository(
            filesystem: $app['files'],
            composer: $app[Composer::class],
            modulesPath: $app['config']->get('modules.path'),
            basePath: $app->basePath(),
            modulesManifestPath: $app['config']->get('modules.cache.enabled')
                ? $app->bootstrapPath('cache/laraneat-modules.php')
                : null
        );
    });

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

    $this->artisan('module:delete article')
        ->assertSuccessful();

    expect($this->modulesRepository->has('laraneat/article'))->toBe(false);
});
