<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\ModulesRepository;

class ModulesRepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModulesRepository::class, function (Application $app) {
            /** @phpstan-ignore-next-line  */
            return new ModulesRepository(
                app: $app,
                modulesPath: $this->app['config']->get('modules.path'),
                modulesManifestPath: $this->app->bootstrapPath('cache/laraneat-modules.php')
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
        return [ModulesRepository::class];
    }
}
