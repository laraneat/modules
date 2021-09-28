<?php

namespace App\Modules\Article\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laraneat\Modules\Traits\RouteProviderHelpersTrait;

class RouteServiceProvider extends ServiceProvider
{
    use RouteProviderHelpersTrait;

    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Article';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
//            ->namespace('App\\Modules\\Article\\UI\\WEB\\Controllers')
            ->group(function () {
                $this->loadRoutesFromDirectory(module_path($this->moduleName, 'UI/WEB/Routes'));
            });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
//            ->namespace('App\\Modules\\Article\\UI\\API\\Controllers')
            ->group(function () {
                $this->loadRoutesFromDirectory(module_path($this->moduleName, 'UI/API/Routes'));
            });
    }
}
