<?php

namespace Modules\ArticleComment\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class ArticleCommentServiceProvider extends ModuleServiceProvider
{
    protected string $modulePackageName = 'demo/article-comment';

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
        $langPath = lang_path('modules/demo/article-comment');

        $this->loadTranslationsFrom($sourcePath, 'demo/article-comment');

        $this->publishes([
            $sourcePath => $langPath,
        ], 'article-comment-translations');
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $sourcePath = realpath('../../resources/views');
        $viewsPath = resource_path('views/modules/demo/article-comment');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->modulePackageName), [$sourcePath]),
            $this->modulePackageName
        );

        $this->publishes([
            $sourcePath => $viewsPath
        ], 'article-comment-views');
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
            $sourcePath => $migrationsPath
        ], 'article-comment-migrations');
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
