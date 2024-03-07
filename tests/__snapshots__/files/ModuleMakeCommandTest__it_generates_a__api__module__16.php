<?php

namespace Modules\ArticleComment\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class ArticleCommentServiceProvider extends ModuleServiceProvider
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
        $sourcePath = __DIR__.'/../../config/article-comment.php';
        $configsPath = $this->app->configPath('article-comment.php');

        $this->mergeConfigFrom($sourcePath, 'article-comment');

        $this->publishes([
            $sourcePath => $configsPath
        ], 'article-comment-config');
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
        ], 'article-comment-migrations');
    }

    /**
     * Register artisan commands.
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom(
                __DIR__.'/../UI/CLI/Commands',
                __DIR__.'/../..',
                'Modules\\ArticleComment'
            );
        }
    }

    /**
     * Register translations.
     */
    public function loadTranslations(): void
    {
        $sourcePath = __DIR__.'/../../lang';
        $langPath = $this->app->langPath('modules/article-comment');

        $this->loadTranslationsFrom($sourcePath, 'article-comment');

        $this->publishes([
            $sourcePath => $langPath,
        ], 'article-comment-translations');
    }

    /**
     * Register views.
     */
    public function loadViews(): void
    {
        $sourcePath = __DIR__.'/../../resources/views';
        $viewsPath = $this->app->resourcePath('views/modules/article-comment');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths('article-comment'), [$sourcePath]),
            'article-comment'
        );

        $this->publishes([
            $sourcePath => $viewsPath
        ], 'article-comment-views');
    }
}
