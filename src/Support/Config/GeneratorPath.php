<?php

namespace Laraneat\Modules\Support\Config;

class GeneratorPath
{
    private string $path;
    private string $namespace;
    private bool $generate;

    /**
     * @param array|bool|string $config
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = (string) $config['path'];
            $this->generate = (bool) ($config['generate'] ?? true);
            $this->namespace = (string) ($config['namespace'] ?? $this->convertPathToNamespace($this->path));

            return;
        }

        $this->path = (string) $config;
        $this->generate = (bool) $config;
        $this->namespace = $this->convertPathToNamespace($this->path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    private function convertPathToNamespace(string $path): string
    {
        return str_replace('/', '\\', $path);
    }
}
