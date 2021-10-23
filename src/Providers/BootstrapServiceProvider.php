<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Contracts\RepositoryInterface;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app[RepositoryInterface::class]->register();
        $this->app[RepositoryInterface::class]->boot();
    }
}
