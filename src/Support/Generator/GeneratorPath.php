<?php

namespace Laraneat\Modules\Support\Generator;

use Laraneat\Modules\Module;

class GeneratorPath
{
    private string $path;
    private string $namespace;
    private bool $generate;
    private bool $gitkeep;

    /**
     * @param array|bool|string $config
     */
    public function __construct($config)
    {
        if (!is_array($config)) {
            $config = [
                'path' => (string) $config,
                'generate' => (bool) $config
            ];
        }

        $this->path = $this->formatPath((string) $config['path']);
        $this->namespace = $this->formatNamespace(
            $this->convertPathToNamespace((string) ($config['namespace'] ?? $this->path))
        );
        $this->generate = (bool) ($config['generate'] ?? true);
        $this->gitkeep = (bool) ($config['gitkeep'] ?? false);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullPath(Module|string $module): string
    {
        return GeneratorHelper::modulePath($module, $this->path);
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getFullNamespace(Module|string $module): string
    {
        return GeneratorHelper::moduleNamespace($module, $this->namespace);
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    public function withGitKeep(): bool
    {
        return $this->gitkeep;
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
