<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Laravel\LaravelFileRepository;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    public function register()
    {
        $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
    }
}
