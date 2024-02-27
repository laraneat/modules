<?php

namespace Modules\ArticleCategory\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laraneat\Modules\Concerns\CanLoadRoutesFromDirectory;

class RouteServiceProvider extends ServiceProvider
{
    use CanLoadRoutesFromDirectory;

    /**
     * Called before routes are registered.
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            // ->namespace('Modules\\ArticleCategory\\UI\\WEB\\Controllers')
            ->group(function () {
                $this->loadRoutesFromDirectory(realpath('../UI/WEB/routes'));
            });
    }

    /**
     * Define the "api" routes for the application.
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            // ->namespace('Modules\\ArticleCategory\\UI\\API\\Controllers')
            ->group(function () {
                $this->loadRoutesFromDirectory(realpath('../UI/API/routes'));
            });
    }
}
