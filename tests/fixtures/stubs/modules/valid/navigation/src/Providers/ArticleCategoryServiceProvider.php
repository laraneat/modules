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
        $this->loadMigrations();
        // $this->loadTranslations();
        // $this->loadCommands();
        // $this->loadViews();
    }

    /**
     * Register translations.
     */
    public function loadTranslations(): void
    {
        $sourcePath = realpath('../../lang');
        $langPath = $this->app->langPath('modules/' . $this->modulePackageName);

        $this->loadTranslationsFrom($sourcePath, $this->modulePackageName);

        $this->publishes([
            $sourcePath => $langPath,
        ], 'geo-location-translations');
    }

    /**
     * Register views.
     */
    public function loadViews(): void
    {
        $sourcePath = realpath('../../resources/views');
        $viewsPath = $this->app->resourcePath('views/modules/' . $this->modulePackageName);

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
    public function loadMigrations(): void
    {
        $sourcePath = realpath('../../database/migrations');
        $migrationsPath = $this->app->databasePath('migrations');

        $this->loadMigrationsFrom($sourcePath);

        $this->publishes([
            $sourcePath => $migrationsPath,
        ], 'geo-location-migrations');
    }

    /**
     * Register artisan commands.
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom(realpath('../UI/CLI/Commands'));
        }
    }
}
