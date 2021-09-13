<?php

namespace App\Modules\Recipe\Providers;

use Illuminate\Support\ServiceProvider;

class RecipeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    private function registerBindings()
    {
        $this->app->bind(
            \App\Modules\Recipe\Repositories\RecipeRepository::class,
            function () {
                $repository = new \App\Modules\Recipe\Repositories\Eloquent\EloquentRecipeRepository(new \App\Modules\Recipe\Entities\Recipe());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \App\Modules\Recipe\Repositories\Cache\CacheRecipeDecorator($repository);
            }
        );
        // add bindings
    }
}
