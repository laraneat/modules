<?php

namespace Modules\Author\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class AuthorServiceProvider extends ModuleServiceProvider
{
    protected string $modulePackageName = 'laraneat/author';

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
        $sourcePath = $this->getModule()->subPath('lang');
        $langPath = lang_path('modules/' . $this->modulePackageName);

        $this->loadTranslationsFrom($sourcePath, $this->modulePackageName);

        $this->publishes([
            $sourcePath => $langPath,
        ], 'author-translations');
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $sourcePath = $this->getModule()->subPath('resources/views');
        $viewsPath = resource_path('views/modules/' . $this->modulePackageName);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->modulePackageName), [$sourcePath]),
            $this->modulePackageName
        );

        $this->publishes([
            $sourcePath => $viewsPath,
        ], 'author-views');
    }

    /**
     * Register migrations.
     */
    public function registerMigrations(): void
    {
        $sourcePath = $this->getModule()->subPath('database/migrations');
        $migrationsPath = database_path('migrations');

        $this->loadMigrationsFrom($sourcePath);

        $this->publishes([
            $sourcePath => $migrationsPath,
        ], 'author-migrations');
    }

    /**
     * Register artisan commands.
     */
    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom($this->getModule()->subPath('src/UI/CLI/Commands'));
        }
    }
}