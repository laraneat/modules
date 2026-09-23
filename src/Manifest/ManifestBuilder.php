<?php

declare(strict_types=1);

namespace Laraneat\Modules\Manifest;

use JsonException;
use Laraneat\Modules\Exceptions\InvalidModule;
use Laraneat\Modules\Scaffold\Names;

/**
 * Scans the modules directory once and records everything the provider loads, so that
 * nothing has to touch the filesystem again while the application boots.
 *
 * Paths inside a module entry are relative to the module directory.
 *
 * @internal
 *
 * @phpstan-type ModuleManifest array{
 *     package: string,
 *     namespace: string,
 *     path: string,
 *     src: string,
 *     config: list<string>,
 *     lang: bool,
 *     json: bool,
 *     views: bool,
 *     migrations: bool,
 *     routes: array<string, array<string, list<string>>>,
 *     seeders: array<string, list<string>>,
 *     commands: list<string>,
 * }
 * @phpstan-type Manifest array<string, ModuleManifest>
 */
final readonly class ManifestBuilder
{
    private string $modulesPath;

    private string $commandsNamespace;

    /**
     * @param  array<string, string>  $routes  The route directory of every route group.
     */
    public function __construct(
        string $modulesPath,
        private array $routes,
        string $commandsNamespace,
    ) {
        $this->modulesPath = rtrim(str_replace('\\', '/', $modulesPath), '/');
        $this->commandsNamespace = trim($commandsNamespace, '\\');
    }

    /**
     * Identifies the settings the manifest is built with.
     */
    public function key(): string
    {
        return hash('xxh128', serialize([$this->modulesPath, $this->routes, $this->commandsNamespace]));
    }

    public function modulesPath(): string
    {
        return $this->modulesPath;
    }

    /**
     * @return Manifest
     */
    public function build(): array
    {
        $manifest = [];
        $packages = [];

        foreach ($this->scan($this->modulesPath)[1] as $name) {
            $path = $this->modulesPath.'/'.$name;

            if (! is_file($path.'/composer.json')) {
                continue;
            }

            $module = $this->module($path);

            if (isset($packages[$module['package']])) {
                throw InvalidModule::at($path, "the package name [{$module['package']}] is already used by the [{$packages[$module['package']]}] module.");
            }

            $packages[$module['package']] = $name;
            $manifest[$name] = $module;
        }

        return $manifest;
    }

    /**
     * @return ModuleManifest
     */
    private function module(string $path): array
    {
        $composer = $this->composerJson($path);

        $package = $composer['name'] ?? null;

        if (! is_string($package) || ! Names::isPackage($package)) {
            throw InvalidModule::at($path, 'composer.json must have a valid "name".');
        }

        $autoload = is_array($composer['autoload'] ?? null) ? ($composer['autoload']['psr-4'] ?? null) : null;
        $namespace = is_array($autoload) ? array_key_first($autoload) : null;
        $source = $namespace === null ? null : (is_array($autoload[$namespace]) ? ($autoload[$namespace][0] ?? null) : $autoload[$namespace]);

        if (! is_string($namespace) || ! Names::isNamespace(rtrim($namespace, '\\')) || ! str_ends_with($namespace, '\\') || ! is_string($source)) {
            throw InvalidModule::at($path, 'the first "autoload.psr-4" entry of composer.json must map the root namespace of the module (e.g. "Modules\\\\Blog\\\\": "src/").');
        }

        $namespace = rtrim($namespace, '\\');
        $source = trim((string) preg_replace('{^\./}', '', str_replace('\\', '/', $source)), '/');
        $lang = is_dir($path.'/lang');
        $json = $lang && preg_grep('/\.json$/D', scandir($path.'/lang') ?: []) !== [];

        return [
            'package' => $package,
            'namespace' => $namespace,
            'path' => $path,
            'src' => $source,
            'config' => array_map(static fn (string $file): string => substr($file, 0, -4), $this->scan($path.'/config')[0]),
            'lang' => $lang,
            'json' => $json,
            'views' => is_dir($path.'/resources/views'),
            'migrations' => is_dir($path.'/database/migrations'),
            'routes' => $this->routes($path),
            'seeders' => $this->seeders($path, $namespace),
            'commands' => $this->commands($path, $source, $namespace),
        ];
    }

    /**
     * @return array<mixed>
     */
    private function composerJson(string $path): array
    {
        try {
            $composer = json_decode((string) file_get_contents($path.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidModule::at($path, 'composer.json is not valid JSON: '.$exception->getMessage(), $exception);
        }

        if (! is_array($composer)) {
            throw InvalidModule::at($path, 'composer.json must contain an object.');
        }

        return $composer;
    }

    /**
     * Route files by route group, then by the prefix of their directory: files of a directory
     * come before its subdirectories, everything in alphabetical order.
     *
     * @return array<string, array<string, list<string>>>
     */
    private function routes(string $path): array
    {
        $routes = [];

        foreach ($this->routes as $group => $directory) {
            $directory = trim(str_replace('\\', '/', $directory), '/');

            if (is_dir($path.'/'.$directory)) {
                $routes[$group] = $this->routeFiles($path, $directory, '');
            }
        }

        return $routes;
    }

    /**
     * @return array<string, list<string>>
     */
    private function routeFiles(string $path, string $directory, string $prefix): array
    {
        [$files, $directories] = $this->scan($path.'/'.$directory);
        $routes = [];

        foreach ($files as $file) {
            $routes[$prefix][] = $directory.'/'.$file;
        }

        foreach ($directories as $subdirectory) {
            $routes += $this->routeFiles($path, $directory.'/'.$subdirectory, ltrim($prefix.'/'.$subdirectory, '/'));
        }

        return $routes;
    }

    /**
     * Seeder classes of "database/seeders" (key "") and of its direct subdirectories.
     *
     * @return array<string, list<string>>
     */
    private function seeders(string $path, string $namespace): array
    {
        $directory = $path.'/database/seeders';
        $namespace .= '\\Database\\Seeders\\';
        [$files, $directories] = $this->scan($directory);
        $seeders = [];

        foreach ($this->classes($files) as $class) {
            $seeders[''][] = $namespace.$class;
        }

        foreach (array_filter($directories, $this->isIdentifier(...)) as $subdirectory) {
            foreach ($this->classes($this->scan($directory.'/'.$subdirectory)[0]) as $class) {
                $seeders[$subdirectory][] = $namespace.$subdirectory.'\\'.$class;
            }
        }

        return $seeders;
    }

    /**
     * Classes of the "make:command" namespace, including its subdirectories.
     *
     * @return list<string>
     */
    private function commands(string $path, string $source, string $namespace): array
    {
        $directory = $path.($source === '' ? '' : '/'.$source).'/'.str_replace('\\', '/', $this->commandsNamespace);

        return $this->classesIn($directory, $namespace.'\\'.$this->commandsNamespace.'\\');
    }

    /**
     * @return list<string>
     */
    private function classesIn(string $directory, string $namespace): array
    {
        [$files, $directories] = $this->scan($directory);
        $classes = array_map(static fn (string $class): string => $namespace.$class, $this->classes($files));

        foreach (array_filter($directories, $this->isIdentifier(...)) as $subdirectory) {
            array_push($classes, ...$this->classesIn($directory.'/'.$subdirectory, $namespace.$subdirectory.'\\'));
        }

        return $classes;
    }

    /**
     * Class names of PHP files.
     *
     * @param  list<string>  $files
     * @return list<string>
     */
    private function classes(array $files): array
    {
        return array_values(array_filter(
            array_map(static fn (string $file): string => substr($file, 0, -4), $files),
            $this->isIdentifier(...),
        ));
    }

    /**
     * PHP files and subdirectories of a directory, sorted, without hidden entries.
     *
     * @return array{list<string>, list<string>}
     */
    private function scan(string $directory): array
    {
        $entries = is_dir($directory) ? (scandir($directory) ?: []) : [];
        $files = [];
        $directories = [];

        sort($entries, SORT_STRING);

        foreach ($entries as $entry) {
            if ($entry[0] === '.') {
                continue;
            }

            if (is_dir($directory.'/'.$entry)) {
                $directories[] = $entry;
            } elseif (str_ends_with($entry, '.php')) {
                $files[] = $entry;
            }
        }

        return [$files, $directories];
    }

    private function isIdentifier(string $name): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) === 1;
    }
}
