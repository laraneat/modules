<?php

namespace Laraneat\Modules\Support;

use Laraneat\Modules\Support\Generator\GeneratorHelper;

class Stub
{
    /**
     * The stub path.
     *
     * @var string
     */
    protected string $path;

    /**
     * The replacements array.
     *
     * @var array
     */
    protected array $replaces = [];

    /**
     * @param string $path
     * @param array  $replaces
     */
    public function __construct(string $path, array $replaces = [])
    {
        $this->path = $path;
        $this->replaces = $replaces;
    }

    /**
     * Create new self instance.
     *
     * @param string $path
     * @param array  $replaces
     *
     * @return static
     */
    public static function create(string $path, array $replaces = [])
    {
        return new static($path, $replaces);
    }

    /**
     * Set stub path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get stub path.
     *
     * @return string
     */
    public function getPath(): string
    {
        $customStubsFolderPath = GeneratorHelper::customStubsPath();
        $customStubFilePath = $customStubsFolderPath . '/' . ltrim($this->path, '/');

        if (file_exists($customStubFilePath)) {
            return $customStubFilePath;
        }

        return __DIR__ . '/../Commands/Generators/stubs/' . ltrim($this->path, '/');
    }

    /**
     * Get stub contents.
     *
     * @return string
     */
    public function getContents(): string
    {
        $contents = file_get_contents($this->getPath());

        foreach ($this->replaces as $search => $replace) {
            $contents = str_replace(
                ['{{' . $search . '}}', '{{ ' . $search . ' }}'],
                $replace,
                $contents
            );
        }

        return $contents;
    }

    /**
     * Get stub contents.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->getContents();
    }

    /**
     * Save stub to specific path.
     *
     * @param string $path
     * @param string $filename
     *
     * @return int|false
     */
    public function saveTo(string $path, string $filename)
    {
        return file_put_contents($path . '/' . $filename, $this->getContents());
    }

    /**
     * Set replacements array.
     *
     * @param array $replaces
     *
     * @return $this
     */
    public function replace(array $replaces = [])
    {
        $this->replaces = $replaces;

        return $this;
    }

    /**
     * Get replacements.
     *
     * @return array
     */
    public function getReplaces(): array
    {
        return $this->replaces;
    }

    /**
     * Handle magic method __toString.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
