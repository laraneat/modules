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
        $this->loadMigrations();
        // $this->loadTranslations();
        // $this->loadCommands();
        // $this->loadViews();
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
     * Register translations.
     */
    public function loadTranslations(): void
    {
        $sourcePath = __DIR__.'/../../lang';
        $langPath = $this->app->langPath('modules/demo/article-comment');

        $this->loadTranslationsFrom($sourcePath, 'demo/article-comment');

        $this->publishes([
            $sourcePath => $langPath,
        ], 'article-comment-translations');
    }

    /**
     * Register artisan commands.
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommandsFrom(__DIR__.'/../UI/CLI/Commands');
        }
    }

    /**
     * Register views.
     */
    public function loadViews(): void
    {
        $sourcePath = __DIR__.'/../../resources/views';
        $viewsPath = $this->app->resourcePath('views/modules/demo/article-comment');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->modulePackageName), [$sourcePath]),
            $this->modulePackageName
        );

        $this->publishes([
            $sourcePath => $viewsPath
        ], 'article-comment-views');
    }
}
