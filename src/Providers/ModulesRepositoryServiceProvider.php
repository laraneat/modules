<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Composer;
use Laraneat\Modules\Support\ModuleConfigWriter;

class ModulesRepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModulesRepository::class, function (Application $app) {
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

        $this->app->singleton(ModuleConfigWriter::class, function (Application $app) {
            return new ModuleConfigWriter(
                modulesRepository: $app[ModulesRepository::class],
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            ModulesRepository::class,
            ModuleConfigWriter::class,
        ];
    }
}
