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

it('removes the composer package before deleting the module files', function () {
    $modulePath = $this->modulesRepository->findOrFail('laraneat/article')->getPath();

    $composer = Mockery::mock(Composer::class);
    $composer->shouldReceive('removePackages')
        ->once()
        ->withArgs(function (array $packages) use ($modulePath) {
            // the files must still exist while composer runs
            expect(is_dir($modulePath))->toBeTrue();

            return $packages === ['laraneat/article'];
        })
        ->andReturn(true);
    $this->app->instance(Composer::class, $composer);
    $this->app->forgetInstance(ModulesRepository::class);

    $this->artisan('module:delete article')
        ->assertSuccessful();

    expect(is_dir($modulePath))->toBeFalse();
});

it('keeps the module files and fails when composer cannot remove the package', function () {
    $modulePath = $this->modulesRepository->findOrFail('laraneat/article')->getPath();

    $composer = Mockery::mock(Composer::class);
    $composer->shouldReceive('removePackages')->once()->andReturn(false);
    $this->app->instance(Composer::class, $composer);
    $this->app->forgetInstance(ModulesRepository::class);

    $this->artisan('module:delete article')
        ->expectsOutputToContain('Failed to remove package with composer.')
        ->assertFailed();

    expect(is_dir($modulePath))->toBeTrue();
});

it('refuses to delete a symlinked module and leaves its target intact', function () {
    $target = sys_get_temp_dir() . '/laraneat-symlink-target-' . uniqid();
    $this->filesystem->copyDirectory(__DIR__ . '/../fixtures/stubs/modules/valid/navigation', $target);
    $this->filesystem->deleteDirectory($this->app->basePath('/modules/navigation'));
    symlink($target, $this->app->basePath('/modules/navigation'));
    $this->app[ModulesRepository::class]->pruneModulesManifest();

    $composer = Mockery::mock(Composer::class);
    $composer->shouldNotReceive('removePackages');
    $this->app->instance(Composer::class, $composer);
    $this->app->forgetInstance(ModulesRepository::class);

    try {
        $this->artisan('module:delete navigation')
            ->expectsOutputToContain('is a symbolic link')
            ->assertFailed();

        expect(is_link($this->app->basePath('/modules/navigation')))->toBeTrue()
            ->and(is_file($target . '/composer.json'))->toBeTrue();
    } finally {
        @unlink($this->app->basePath('/modules/navigation'));
        $this->filesystem->deleteDirectory($target);
    }
});
