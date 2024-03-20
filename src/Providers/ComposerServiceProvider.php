<?php

namespace Laraneat\Modules\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Support\Composer;

class ComposerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Composer::class, function (Application $app) {
            return new Composer($app['files'], $app->basePath());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [Composer::class];
    }
}
