<?php

namespace App\Modules\Article\Providers;

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Traits\ModuleProviderHelpersTrait;

class ArticleServiceProvider extends ServiceProvider
{
    use ModuleProviderHelpersTrait;

    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'Article';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'article';

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->registerCommands();
        // $this->registerTranslations();
        // $this->registerViews();
        // $this->registerMigrations();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadCommands(module_path($this->moduleName, 'UI/CLI/Commands'));
        }
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations(): void
    {
        $sourcePath = module_path($this->moduleName, 'Resources/lang');
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        $viewsPath = resource_path('views/modules/' . $this->moduleNameLower);

        $this->publishes([
            $sourcePath => $viewsPath
        ], 'views');

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths($this->moduleNameLower), [$sourcePath]),
            $this->moduleNameLower
        );
    }

    /**
     * Register migrations.
     *
     * @return void
     */
    public function registerMigrations(): void
    {
        $sourcePath = module_path($this->moduleName, 'Data/Migrations');
        $migrationsPath = database_path('migrations');

        $this->publishes([
            $sourcePath => $migrationsPath
        ], 'migrations');

        $this->loadMigrationsFrom($sourcePath);
    }
}
