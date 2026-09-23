<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Laraneat\Modules\Exceptions\ModulesException;
use Laraneat\Modules\Manifest\ManifestBuilder;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerJson;
use Laraneat\Modules\Scaffold\InstalledPackages;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Read-only checks of the modules and of how they are installed.
 *
 * @phpstan-import-type ModuleManifest from ManifestBuilder
 */
#[AsCommand(name: 'module:doctor', description: 'Check the modules and how they are installed')]
final class DoctorCommand extends Command
{
    private const array CONFIG_KEYS = ['path', 'namespace', 'vendor', 'routes', 'generators'];

    protected $signature = 'module:doctor';

    private int $errors = 0;

    private int $warnings = 0;

    public function handle(ModuleRepository $modules, ApplicationComposer $composer): int
    {
        $this->errors = $this->warnings = 0;

        try {
            $root = $composer->composerJson();
            $installed = $composer->installed($root);
            $manifest = $modules->manifest();

            $this->report('Configuration', $this->checkConfig($modules, $composer));

            foreach ($modules->all() as $name => $module) {
                $this->report($name, $this->checkModule($module, $manifest[$name], $root, $installed, $composer));
            }
        } catch (ModulesException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        if ($this->errors > 0) {
            $this->components->error("Found {$this->errors} error(s) and {$this->warnings} warning(s).");

            return self::FAILURE;
        }

        $this->warnings > 0
            ? $this->components->warn("Found {$this->warnings} warning(s).")
            : $this->components->info('No problems found.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{bool, string}> Problems: whether it is an error, and the message.
     */
    private function checkConfig(ModuleRepository $modules, ApplicationComposer $composer): array
    {
        $config = $this->config();
        $problems = [];

        if (($unknown = array_diff(array_keys((array) $config->get('modules', [])), self::CONFIG_KEYS)) !== []) {
            $problems[] = [false, 'Unknown keys in config/modules.php: '.implode(', ', $unknown).'.'];
        }

        if ($modules->isCacheStale()) {
            $problems[] = [false, 'The module cache is outdated: run "php artisan module:cache".'];
        }

        if (is_array($config->get('octane.watch')) && $composer->relative($modules->modulesPath()) === $modules->modulesPath()) {
            $problems[] = [false, 'The modules path is outside of the application, so "octane:start --watch" does not watch it.'];
        }

        return $problems;
    }

    /**
     * @param  ModuleManifest  $manifest
     * @return list<array{bool, string}>
     */
    private function checkModule(Module $module, array $manifest, ComposerJson $root, InstalledPackages $installed, ApplicationComposer $composer): array
    {
        $composerJson = ComposerJson::read($module->path.'/composer.json');
        $problems = [];

        if (! $root->requires($module->package)) {
            $problems[] = [true, "The application does not require [{$module->package}]: run \"php artisan module:sync\"."];
        }

        if (! $root->isExcludedFromPackagist($module->package)) {
            $problems[] = [true, "[{$module->package}] can be installed from Packagist: run \"php artisan module:sync\" to exclude the vendor."];
        }

        if (! $installed->has($module->package)) {
            $problems[] = [true, "[{$module->package}] is not installed: run \"php artisan module:sync\"."];
        } elseif (realpath($vendorPath = $installed->path($module->package)) !== realpath($module->path)) {
            $source = $installed->pathSource($module->package);

            // COMPOSER_MIRROR_PATH_REPOS=1 copies path packages, as Docker images often do.
            $problems[] = $source !== null && ! is_link($vendorPath) && realpath($composer->absolute($source)) === realpath($module->path)
                ? [false, "[{$composer->relative($vendorPath)}] is a copy, not a link to [{$composer->relative($module->path)}]: changes of the module need \"composer update\"."]
                : [true, "[{$composer->relative($vendorPath)}] is not a link to [{$composer->relative($module->path)}]."];
        } elseif (! $installed->isCurrent($module->package, $composerJson->toArray())) {
            $problems[] = [false, 'composer.json changed since the module was installed: run "php artisan module:sync".'];
        }

        $autoloadDev = (array) $root->get('autoload-dev.psr-4');

        foreach ($composer->autoloadDev($module) as $namespace => $paths) {
            if (($autoloadDev[$namespace] ?? null) !== $paths) {
                $problems[] = [false, "The application does not autoload [{$namespace}] for development: run \"php artisan module:sync\"."];
            }
        }

        if (! is_dir($module->sourcePath)) {
            $problems[] = [true, "The directory of the [{$module->namespace}] namespace does not exist."];
        }

        $autoload = (array) $composerJson->get('autoload.psr-4');

        foreach (['database/factories' => 'Database\\Factories', 'database/seeders' => 'Database\\Seeders'] as $directory => $namespace) {
            $namespace = $module->namespace.'\\'.$namespace.'\\';
            $mapped = $autoload[$namespace] ?? null;

            if (is_dir($module->path.'/'.$directory) && (! is_string($mapped) || trim((string) preg_replace('{^\./}', '', $mapped), '/') !== $directory)) {
                $problems[] = [true, "\"autoload.psr-4\" must map [{$namespace}] to [{$directory}/]."];
            }
        }

        foreach ((array) $composerJson->get('extra.laravel.providers') as $provider) {
            if (! is_string($provider) || ! class_exists($provider)) {
                $problems[] = [true, 'The provider ['.(is_string($provider) ? $provider : get_debug_type($provider)).'] does not exist.'];
            }
        }

        foreach ($this->unloadedRouteFiles($module, $manifest) as $file) {
            $problems[] = [false, "The route file [{$file}] is not in a route group of config/modules.php."];
        }

        return $problems;
    }

    /**
     * PHP files in "routes" directories of the module that no route group loads.
     *
     * @param  ModuleManifest  $manifest
     * @return list<string>
     */
    private function unloadedRouteFiles(Module $module, array $manifest): array
    {
        $loaded = array_merge(...array_values(array_map(
            static fn (array $directories): array => array_merge(...array_values($directories)),
            $manifest['routes'],
        )));

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($module->path, FilesystemIterator::SKIP_DOTS),
                static fn (SplFileInfo $file): bool => ! $file->isDir() || preg_match('{^(vendor|node_modules|tests|\..*)$}', $file->getFilename()) !== 1,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );
        $iterator->setMaxDepth(6);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = substr(str_replace('\\', '/', $file->getPathname()), strlen($module->path) + 1);

            if ($file->getExtension() === 'php' && preg_match('{(^|/)routes/}', $path) === 1 && ! in_array($path, $loaded, true)) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param  list<array{bool, string}>  $problems
     */
    private function report(string $subject, array $problems): void
    {
        if ($problems === []) {
            $this->components->twoColumnDetail($subject, '<fg=green;options=bold>OK</>');

            return;
        }

        $errors = count(array_filter($problems, static fn (array $problem): bool => $problem[0]));
        $this->errors += $errors;
        $this->warnings += count($problems) - $errors;

        $this->components->twoColumnDetail($subject, $errors > 0 ? '<fg=red;options=bold>FAIL</>' : '<fg=yellow;options=bold>WARN</>');

        foreach ($problems as [$isError, $message]) {
            $this->line('    '.($isError ? '<fg=red>✗</>' : '<fg=yellow>!</>').' '.$message);
        }
    }

    private function config(): Repository
    {
        return $this->laravel->make('config');
    }
}
