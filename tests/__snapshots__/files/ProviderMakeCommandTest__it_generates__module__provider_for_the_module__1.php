<?php

namespace Modules\Author\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class CustomModuleServiceProvider extends ModuleServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // $this->loadConfigurations();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrations();
        // $this->loadCommands();
        // $this->loadTranslations();
        // $this->loadViews();
    }

    /**
     * Register configuration files.
     */
    public function loadConfigurations(): void
    {
        $sourcePath = __DIR__.'/../../config/author.php';
        $configsPath = $this->app->configPath('author.php');

        $this->mergeConfigFrom($sourcePath, 'author');

        $this->publishes([
            $sourcePath => $configsPath
        ], 'author-config');
    }

    /**
     * Register migrations.
     */
    public function loadMigrations(): void
    {
        $sourcePath = __DIR__.'/../../database/migrations';
        $migrationsPath = $this->app->databasePath('migrations');

        $this->loadMigrationsFrom($sourcePath);

        $this->publishes([
            $sourcePath => $migrationsPath
        ], 'author-migrations');
    }

    /**
     * Register artisan commands.
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom([
                'Modules\\Author\\UI\\CLI\\Commands' => __DIR__.'/../UI/CLI/Commands',
            ]);
        }
    }

    /**
     * Register translations.
     */
    public function loadTranslations(): void
    {
        $sourcePath = __DIR__.'/../../lang';
        $langPath = $this->app->langPath('modules/author');

        $this->loadTranslationsFrom($sourcePath, 'author');

        $this->publishes([
            $sourcePath => $langPath,
        ], 'author-translations');
    }

    /**
     * Register views.
     */
    public function loadViews(): void
    {
        $sourcePath = __DIR__.'/../../resources/views';
        $viewsPath = $this->app->resourcePath('views/modules/author');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths('author'), [$sourcePath]),
            'author'
        );

        $this->publishes([
            $sourcePath => $viewsPath
        ], 'author-views');
    }
}
