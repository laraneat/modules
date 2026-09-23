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
     */
    public function __construct(
        private Application $app,
        private array $manifest,
    ) {}

    /**
     * Merge the config files of the modules; the application config wins. Runs in register(),
     * so the config is complete before any provider boots.
     */
    public function registerConfig(): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $config = $this->app->make('config');

        foreach ($this->manifest as $module) {
            foreach ($module['config'] as $key) {
                $values = require $module['path'].'/config/'.$key.'.php';

                if (! is_array($values)) {
                    throw InvalidModule::at($module['path'], "config/{$key}.php must return an array.");
                }

                $config->set($key, array_merge($values, (array) $config->get($key, [])));
            }
        }
    }

    public function boot(): void
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
            // <x-blog::alert /> renders Modules\Blog\View\Components\Alert, or the "blog::components.alert" view.
            $this->afterResolving('blade.compiler', function (BladeCompiler $blade): void {
                foreach ($this->manifest as $name => $module) {
                    $blade->componentNamespace($module['namespace'].'\\View\\Components', $name);
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
