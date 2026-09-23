<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Closure;
use Illuminate\Foundation\Application;
use Laraneat\Modules\Module;
use ReflectionProperty;

/**
 * Points the application at a module while a generator runs: the app path, the namespace,
 * the database, config and view paths. Commands called by the generator (make:model -mfs)
 * inherit it. Everything is restored afterwards, even when the generator fails.
 *
 * @internal
 */
final class ModuleContext
{
    private ?Module $module = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    public function module(): ?Module
    {
        return $this->module;
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Module $module, Closure $callback): mixed
    {
        $config = $this->app->make('config');
        $namespace = new ReflectionProperty(Application::class, 'namespace');

        $previous = [
            $this->module,
            $this->app->path(),
            $this->app->databasePath(),
            $this->app->configPath(),
            $namespace->getValue($this->app),
            $config->get('view.paths'),
        ];

        $this->module = $module;
        $this->app->useAppPath($module->sourcePath);
        $this->app->useDatabasePath($module->path.'/database');
        $this->app->useConfigPath($module->path.'/config');
        $namespace->setValue($this->app, $module->namespace.'\\');
        $config->set('view.paths', [$module->path.'/resources/views']);

        try {
            return $callback();
        } finally {
            [$this->module, $appPath, $databasePath, $configPath, $previousNamespace, $viewPaths] = $previous;

            $this->app->useAppPath($appPath);
            $this->app->useDatabasePath($databasePath);
            $this->app->useConfigPath($configPath);
            $namespace->setValue($this->app, $previousNamespace);
            $config->set('view.paths', $viewPaths);
        }
    }
}
