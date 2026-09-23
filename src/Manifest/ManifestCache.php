<?php

declare(strict_types=1);

namespace Laraneat\Modules\Manifest;

use Illuminate\Filesystem\Filesystem;

/**
 * The manifest cache file. Module paths are stored relative to the base path,
 * so a cache built in CI or a Docker build works in any directory.
 *
 * @internal
 *
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final readonly class ManifestCache
{
    private string $basePath;

    public function __construct(
        private Filesystem $files,
        private string $path,
        string $basePath,
    ) {
        $this->basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * @return Manifest
     */
    public function read(): array
    {
        /** @var Manifest $manifest */
        $manifest = require $this->path;

        return array_map(fn (array $module): array => [...$module, 'path' => $this->absolute($module['path'])], $manifest);
    }

    /**
     * @param  Manifest  $manifest
     */
    public function write(array $manifest): void
    {
        $manifest = array_map(fn (array $module): array => [...$module, 'path' => $this->relative($module['path'])], $manifest);

        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->replace($this->path, '<?php return '.var_export($manifest, true).';'.PHP_EOL);
    }

    public function delete(): void
    {
        if ($this->exists()) {
            $this->files->delete($this->path);
        }
    }

    private function relative(string $path): string
    {
        return str_starts_with($path, $this->basePath.'/') ? substr($path, strlen($this->basePath) + 1) : $path;
    }

    private function absolute(string $path): string
    {
        return preg_match('{^(/|[A-Za-z]:[/\\\\])}', $path) === 1 ? $path : $this->basePath.'/'.$path;
    }
}
