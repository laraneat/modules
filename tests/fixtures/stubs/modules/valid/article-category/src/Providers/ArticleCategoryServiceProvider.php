<?php

namespace Modules\ArticleCategory\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class ArticleCategoryServiceProvider extends ModuleServiceProvider
{
    protected string $modulePackageName = 'laraneat/article-category';

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
        $langPath = lang_path('modules/' . $this->modulePackageName);

        $this->loadTranslationsFrom($sourcePath, $this->modulePackageName);

        $this->publishes([
            $sourcePath => $langPath,
        ], 'article-category-translations');
    }

    /**
     * Register views.
     */
    public function loadViews(): void
    {
        $sourcePath = realpath('../../resources/views');
        $viewsPath = resource_path('views/modules/' . $this->modulePackageName);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->modulePackageName), [$sourcePath]),
            $this->modulePackageName
        );

        $this->publishes([
            $sourcePath => $viewsPath,
        ], 'article-category-views');
    }

    /**
     * Register migrations.
     */
    public function loadMigrations(): void
    {
        $sourcePath = realpath('../../database/migrations');
        $migrationsPath = database_path('migrations');

        $this->loadMigrationsFrom($sourcePath);

        $this->publishes([
            $sourcePath => $migrationsPath,
        ], 'article-category-migrations');
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
