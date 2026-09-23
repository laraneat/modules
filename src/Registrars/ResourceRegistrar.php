<?php

declare(strict_types=1);

namespace Laraneat\Modules\Registrars;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Laraneat\Modules\Exceptions\InvalidModule;
use Laraneat\Modules\Manifest\ManifestBuilder;

/**
 * Config, translations, views, Blade components and migrations of the modules.
 *
 * Everything is registered while the package provider registers: module providers may boot
 * before it, and they can use the module views and translations. The callbacks run only when
 * the translator, the view factory, Blade or the migrator is resolved.
 *
 * Views and translations are added in one callback for all modules, without the
 * "vendor/<namespace>" override lookup of loadViewsFrom(): modules belong to the
 * application. JSON translation paths are added only for modules that have them.
 *
 * @internal
 *
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final readonly class ResourceRegistrar
{
    /**
     * @param  Manifest  $manifest
     * @param  string  $componentsNamespace  The namespace of Blade components inside a module.
     */
    public function __construct(
        private Application $app,
        private array $manifest,
        private string $componentsNamespace = 'View\\Components',
    ) {}

    public function register(): void
    {
        $this->registerConfig();
        $this->registerCallbacks();
    }

    /**
     * Merge the config files of the modules; the application config wins.
     * A file missing since the manifest was cached is skipped.
     */
    private function registerConfig(): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $config = $this->app->make('config');

        foreach ($this->manifest as $module) {
            foreach ($module['config'] as $key) {
                if (! is_file($file = $module['path'].'/config/'.$key.'.php')) {
                    continue;
                }

                $values = require $file;

                if (! is_array($values)) {
                    throw InvalidModule::at($module['path'], "config/{$key}.php must return an array.");
                }

                $config->set($key, array_merge($values, (array) $config->get($key, [])));
            }
        }
    }

    private function registerCallbacks(): void
    {
        $lang = array_filter($this->manifest, static fn (array $module): bool => $module['lang']);
        $views = array_filter($this->manifest, static fn (array $module): bool => $module['views']);

        if ($lang !== []) {
            $this->afterResolving('translator', static function (Translator $translator) use ($lang): void {
                foreach ($lang as $name => $module) {
                    $translator->addNamespace($name, $module['path'].'/lang');

                    if ($module['json']) {
                        $translator->addJsonPath($module['path'].'/lang');
                    }
                }
            });
        }

        if ($views !== []) {
            $this->afterResolving('view', static function (ViewFactory $view) use ($views): void {
                foreach ($views as $name => $module) {
                    $view->addNamespace($name, $module['path'].'/resources/views');
                }
            });
        }

        if ($this->manifest !== []) {
            // <x-blog::alert /> renders the Alert class of the components namespace, or the "blog::components.alert" view.
            $namespace = trim($this->componentsNamespace, '\\');

            $this->afterResolving('blade.compiler', function (BladeCompiler $blade) use ($namespace): void {
                foreach ($this->manifest as $name => $module) {
                    $blade->componentNamespace($module['namespace'].'\\'.$namespace, $name);
                }
            });
        }

        if ($this->app->runningInConsole()) {
            $migrations = array_filter($this->manifest, static fn (array $module): bool => $module['migrations']);

            if ($migrations !== []) {
                $this->afterResolving('migrator', static function (Migrator $migrator) use ($migrations): void {
                    foreach ($migrations as $module) {
                        $migrator->path($module['path'].'/database/migrations');
                    }
                });
            }
        }
    }

    private function afterResolving(string $abstract, Closure $callback): void
    {
        $this->app->afterResolving($abstract, $callback);

        if ($this->app->resolved($abstract)) {
            $callback($this->app->make($abstract));
        }
    }
}
