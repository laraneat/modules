<?php

declare(strict_types=1);

namespace Laraneat\Modules;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Exceptions\InvalidConfiguration;
use Laraneat\Modules\Generators\ModuleOption;
use Laraneat\Modules\Manifest\ManifestBuilder;
use Laraneat\Modules\Manifest\ManifestCache;
use Laraneat\Modules\Registrars\ConsoleRegistrar;
use Laraneat\Modules\Registrars\ResourceRegistrar;
use Laraneat\Modules\Registrars\RouteRegistrar;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerRunner;

/**
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/modules.php', 'modules');

        $this->app->singleton(ModuleRepository::class, static fn (Application $app): ModuleRepository => new ModuleRepository(
            self::manifestBuilder($app),
            new ManifestCache($app->make('files'), self::cachePath($app), $app->basePath()),
        ));

        (new ResourceRegistrar(
            $this->app,
            $this->manifest(),
            $this->isCached(),
            Config::string('modules.generators.make:component', 'View\\Components'),
        ))->register();

        if ($this->app->runningInConsole()) {
            $this->app->singleton(ApplicationComposer::class, static fn (Application $app): ApplicationComposer => new ApplicationComposer($app->basePath()));
            $this->app->singleton(ComposerRunner::class, static fn (Application $app): ComposerRunner => new ComposerRunner($app->basePath()));

            ModuleOption::register($this->app);
        }
    }

    public function boot(): void
    {
        $manifest = $this->manifest();

        if (! $this->app->routesAreCached()) {
            (new RouteRegistrar($this->app->make('router'), Config::array('modules.routes', []), $manifest, $this->isCached()))->register();
        }

        if ($this->app->runningInConsole()) {
            $this->bootConsole($manifest);
        }
    }

    /**
     * @param  Manifest  $manifest
     */
    private function bootConsole(array $manifest): void
    {
        $modules = $this->app->make(ModuleRepository::class);
        $composer = $this->app->make(ApplicationComposer::class);

        (new ConsoleRegistrar($this->app, $manifest, $modules->modulesPath()))->register();

        $this->commands([
            Commands\CacheCommand::class,
            Commands\ClearCommand::class,
            Commands\DeleteCommand::class,
            Commands\DoctorCommand::class,
            Commands\ListCommand::class,
            Commands\MakeCommand::class,
            Commands\SyncCommand::class,
        ]);

        $this->optimizes(optimize: 'module:cache', clear: 'module:clear', key: 'modules');

        AboutCommand::add('Modules', static fn (): array => [
            'Modules' => count($modules->manifest()),
            'Path' => $composer->relative($modules->modulesPath()),
            'Cache' => AboutCommand::format(
                $modules->isCached(),
                console: static fn (mixed $cached): string => $cached ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            ),
        ]);

        $this->publishes([__DIR__.'/../config/modules.php' => $this->app->configPath('modules.php')], 'modules-config');
        $this->publishes([__DIR__.'/../resources/stubs/module/default' => $this->app->basePath('stubs/module/default')], 'modules-stubs');
    }

    /**
     * @return Manifest
     */
    private function manifest(): array
    {
        return $this->app->make(ModuleRepository::class)->manifest();
    }

    private function isCached(): bool
    {
        return $this->app->make(ModuleRepository::class)->isCached();
    }

    private static function manifestBuilder(Application $app): ManifestBuilder
    {
        $routes = [];
        $generators = Config::array('modules.generators', []);

        foreach (Config::array('modules.routes', []) as $group => $attributes) {
            if (! is_array($attributes) || ! is_string($attributes['path'] ?? null)) {
                throw InvalidConfiguration::because("the [{$group}] route group must have a \"path\".");
            }

            $routes[(string) $group] = $attributes['path'];
        }

        foreach ($generators as $command => $namespace) {
            if (! is_string($namespace)) {
                throw InvalidConfiguration::because("the namespace of the [{$command}] generator must be a string.");
            }
        }

        // An empty namespace would make every class of a module a candidate command.
        if (trim($generators['make:command'] ?? 'Console\\Commands', '\\') === '') {
            throw InvalidConfiguration::because('the namespace of the [make:command] generator can not be empty: module commands are discovered in it.');
        }

        $path = Config::string('modules.path');

        return new ManifestBuilder(
            self::isAbsolute($path) ? $path : $app->basePath($path),
            $routes,
            $generators['make:command'] ?? 'Console\\Commands',
        );
    }

    /**
     * Like the config and route caches: "bootstrap/cache/modules.php", or the MODULES_CACHE path.
     */
    private static function cachePath(Application $app): string
    {
        $path = Env::get('MODULES_CACHE');

        if (! is_string($path) || $path === '') {
            return $app->bootstrapPath('cache/modules.php');
        }

        return self::isAbsolute($path) ? $path : $app->basePath($path);
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('{^[A-Za-z]:[/\\\\]}', $path) === 1;
    }
}
