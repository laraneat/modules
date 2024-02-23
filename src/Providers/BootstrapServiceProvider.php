<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\ModulesRepository;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register module providers and aliases
     */
    public function register(): void
    {
        /** @var ModulesRepository $repository */
        $repository = $this->app[ModulesRepository::class];

        $this->registerProviders($repository);
        $this->registerAliases($repository);
    }

    protected function registerProviders(ModulesRepository $modulesRepository): void
    {
        (new ProviderRepository($this->app, $this->app['files'], $modulesRepository->getCachedModulesServicesPath()))
            ->load($modulesRepository->getProviders());
    }

    protected function registerAliases(ModulesRepository $modulesRepository): void
    {
        foreach ($modulesRepository->getAliases() as $key => $alias) {
            $this->app->alias($key, $alias);
        }
    }
}
