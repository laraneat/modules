<?php

namespace Modules\GeoLocation\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class GeoLocationServiceProvider extends ModuleServiceProvider
{
    protected string $modulePackageName = 'laraneat/location';

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        // $this->registerTranslations();
        // $this->registerCommands();
        // $this->registerViews();
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $sourcePath = realpath('../../lang');
        $langPath = lang_path('modules/' . $this->modulePackageName);

        $this->loadTranslationsFrom($sourcePath, $this->modulePackageName);

        $this->publishes([
            $sourcePath => $langPath,
        ], 'geo-location-translations');
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $sourcePath = realpath('../../resources/views');
        $viewsPath = resource_path('views/modules/' . $this->modulePackageName);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->modulePackageName), [$sourcePath]),
            $this->modulePackageName
        );

        $this->publishes([
            $sourcePath => $viewsPath,
        ], 'geo-location-views');
    }

    /**
     * Register migrations.
     */
    public function registerMigrations(): void
    {
        $sourcePath = realpath('../../database/migrations');
        $migrationsPath = database_path('migrations');

        $this->loadMigrationsFrom($sourcePath);

        $this->publishes([
            $sourcePath => $migrationsPath,
        ], 'geo-location-migrations');
    }

    /**
     * Register artisan commands.
     */
    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom(realpath('../UI/CLI/Commands'));
        }
    }
}