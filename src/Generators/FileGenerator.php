<?php

namespace Laraneat\Modules\Generators;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Exceptions\FileAlreadyExistException;

class FileGenerator extends Generator
{
    /**
     * The path wil be used.
     */
    protected string $path;

    /**
     * The contents will be used.
     */
    protected string $contents;

    /**
     * The laravel filesystem or null.
     */
    protected Filesystem $filesystem;

    /**
     * Flag to overwrite file
     */
    private bool $overwriteFile;

    public function __construct(string $path, string $contents, ?Filesystem $filesystem = null)
    {
        $this->path = $path;
        $this->contents = $contents;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function setContents(string $contents): static
    {
        $this->contents = $contents;

        return $this;
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function setFilesystem(Filesystem $filesystem): static
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function withFileOverwrite(bool $overwrite): FileGenerator
    {
        $this->overwriteFile = $overwrite;

        return $this;
    }

    /**
     * Generate the file.
     *
     * @throws FileAlreadyExistException
     */
    public function generate(): bool|int
    {
        $path = $this->getPath();

        if ($this->overwriteFile === true || ! $this->filesystem->exists($path)) {
            return $this->filesystem->put($path, $this->getContents());
        }

        throw new FileAlreadyExistException();
    }
}
