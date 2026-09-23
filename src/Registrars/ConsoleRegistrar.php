<?php

declare(strict_types=1);

namespace Laraneat\Modules\Registrars;

use Illuminate\Console\Application as Artisan;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laraneat\Modules\Manifest\ManifestBuilder;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * Console-only conventions: module commands, factory name resolution, Tinker aliases
 * and the Octane file watcher. Nothing here runs during HTTP requests.
 *
 * @internal
 *
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final readonly class ConsoleRegistrar
{
    /**
     * @param  Manifest  $manifest
     */
    public function __construct(
        private Application $app,
        private array $manifest,
        private string $modulesPath,
    ) {}

    public function register(): void
    {
        $this->registerCommands();

        if ($this->manifest !== []) {
            $this->registerFactoryResolvers();
            $this->registerTinkerAliases();
        }

        $this->watchWithOctane();
    }

    private function registerCommands(): void
    {
        $commands = array_merge(...array_column($this->manifest, 'commands'));

        if ($commands === []) {
            return;
        }

        Artisan::starting(static function (Artisan $artisan) use ($commands): void {
            $artisan->resolveCommands(array_values(array_filter(
                $commands,
                static fn (string $class): bool => is_subclass_of($class, Command::class) && ! (new ReflectionClass($class))->isAbstract(),
            )));
        });
    }

    /**
     * "Modules\Blog\Models\Post" uses "Modules\Blog\Database\Factories\PostFactory" and back,
     * the way Laravel pairs "App\Models\Post" with "Database\Factories\PostFactory".
     * Classes outside the modules keep the default Laravel behavior.
     */
    private function registerFactoryResolvers(): void
    {
        $namespaces = array_map(static fn (array $module): string => $module['namespace'].'\\', array_values($this->manifest));

        usort($namespaces, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        // @phpstan-ignore argument.type (like the default resolver, it returns a class name that may not exist yet)
        Factory::guessFactoryNamesUsing(static function (string $model) use ($namespaces): string {
            [$root, $factories] = self::roots($model, $namespaces, false);

            $name = str_starts_with($model, $root.'Models\\')
                ? substr($model, strlen($root.'Models\\'))
                : Str::after($model, $root);

            return $factories.$name.'Factory';
        });

        // @phpstan-ignore argument.type (like the default resolver, it returns a class name that may not exist)
        Factory::guessModelNamesUsing(static function (Factory $factory) use ($namespaces): string {
            [$root, $factories] = self::roots($factory::class, $namespaces, true);

            $name = Str::replaceLast('Factory', '', Str::replaceFirst($factories, '', $factory::class));

            foreach ([$root.'Models\\'.$name, $root.$name] as $model) {
                if (class_exists($model)) {
                    return $model;
                }
            }

            return $root.Str::replaceLast('Factory', '', class_basename($factory));
        });
    }

    /**
     * The root namespace and the factories namespace the class belongs to.
     *
     * @param  list<string>  $namespaces
     * @return array{string, string}
     */
    private static function roots(string $class, array $namespaces, bool $isFactory): array
    {
        foreach ($namespaces as $namespace) {
            if (str_starts_with($class, $isFactory ? $namespace.'Database\\Factories\\' : $namespace)) {
                return [$namespace, $namespace.'Database\\Factories\\'];
            }
        }

        try {
            $root = Container::getInstance()->make(ApplicationContract::class)->getNamespace();
        } catch (Throwable) {
            $root = 'App\\';
        }

        return [$root, Factory::$namespace];
    }

    /**
     * Module classes are installed under "vendor", which Tinker does not alias by default.
     */
    private function registerTinkerAliases(): void
    {
        $config = $this->config();
        $aliases = array_filter((array) $config->get('tinker.alias', []), is_string(...));

        foreach ($this->manifest as $module) {
            $aliases[] = $module['namespace'].'\\';
        }

        $config->set('tinker.alias', array_values(array_unique($aliases)));
    }

    /**
     * Let "octane:start --watch" reload the workers when module files change.
     * Octane resolves watched paths against the base path, so only a path inside it can be added.
     */
    private function watchWithOctane(): void
    {
        $config = $this->config();
        $watch = $config->get('octane.watch');
        $basePath = rtrim(str_replace('\\', '/', $this->app->basePath()), '/').'/';

        if (! is_array($watch) || ! str_starts_with($this->modulesPath.'/', $basePath)) {
            return;
        }

        $path = substr($this->modulesPath, strlen($basePath));

        if ($path !== '' && ! in_array($path, $watch, true)) {
            $config->set('octane.watch', [...$watch, $path]);
        }
    }

    private function config(): Repository
    {
        return $this->app->make('config');
    }
}
