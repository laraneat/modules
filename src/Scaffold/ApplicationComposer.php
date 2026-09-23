<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

use Laraneat\Modules\Module;

/**
 * How modules are wired into the Composer setup of the application.
 *
 * @internal
 */
final readonly class ApplicationComposer
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    }

    public function composerJsonPath(): string
    {
        return $this->basePath.'/composer.json';
    }

    public function composerJson(): ComposerJson
    {
        return ComposerJson::read($this->composerJsonPath());
    }

    /**
     * Add the module to the root composer.json and tell whether that changed anything.
     */
    public function addModule(ComposerJson $root, Module $module, string $modulesPath): bool
    {
        $before = $root->contents();

        $root->addModule($module->package, $this->relative($modulesPath).'/*', $this->autoloadDev($module));

        return $root->contents() !== $before;
    }

    /**
     * The "autoload-dev" namespaces of the module (its tests) with paths relative to the application:
     * Composer does not load the dev autoload of dependencies, so the application has to.
     *
     * @return array<string, string|list<string>>
     */
    public function autoloadDev(Module $module): array
    {
        $prefix = $this->relative($module->path).'/';
        $autoloadDev = [];

        foreach ((array) ComposerJson::read($module->path.'/composer.json')->get('autoload-dev.psr-4') as $namespace => $paths) {
            $paths = array_values(array_filter((array) $paths, is_string(...)));

            if (is_string($namespace) && $paths !== []) {
                $paths = array_map(static fn (string $path): string => $prefix.preg_replace('{^\./}', '', $path), $paths);
                $autoloadDev[$namespace] = count($paths) === 1 ? $paths[0] : $paths;
            }
        }

        return $autoloadDev;
    }

    public function installed(ComposerJson $root): InstalledPackages
    {
        $vendor = $root->get('config.vendor-dir');

        return InstalledPackages::read($this->absolute(is_string($vendor) ? $vendor : 'vendor'));
    }

    /**
     * A path relative to the application, if it is inside of it.
     */
    public function relative(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return str_starts_with($path.'/', $this->basePath.'/') ? ltrim(substr($path, strlen($this->basePath)), '/') : $path;
    }

    private function absolute(string $path): string
    {
        return preg_match('{^(/|[A-Za-z]:[/\\\\])}', $path) === 1 ? $path : $this->basePath.'/'.$path;
    }
}
