<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Module;

class GeneratorPath
{
    protected string $path;
    protected string $namespace;

    public function __construct(array $config)
    {
        $this->path = $this->formatPath((string) $config['path']);
        $this->namespace = $this->formatNamespace(
            $this->convertPathToNamespace($config['namespace'] ?? $this->path)
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullPath(Module|string $module): string
    {
        return GeneratorHelper::makeModulePath($module, $this->path);
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getFullNamespace(Module|string $module): string
    {
        return GeneratorHelper::makeModuleNamespace($module, $this->namespace);
    }
    protected function formatPath(string $path): string
    {
        return trim($path, '/');
    }

    protected function formatNamespace(string $namespace): string
    {
        return trim($namespace, '\\');
    }

    protected function convertPathToNamespace(string $path): string
    {
        return str_replace('/', '\\', $path);
    }

    protected function convertNamespaceToPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
