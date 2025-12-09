<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Module;

class GeneratorPath
{
    protected string $path;
    protected string $namespace;

    public function __construct(array $config)
    {
        $this->path = GeneratorHelper::normalizePath((string) $config['path']);
        $this->namespace = GeneratorHelper::normalizeNamespace($config['namespace'] ?? $this->path);
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
}
