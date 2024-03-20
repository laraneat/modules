<?php

namespace Laraneat\Modules\Support\Generator;

class Stub
{
    /**
     * The stub path.
     */
    protected string $path;

    /**
     * The replacements array.
     *
     * @var array<string, string>
     */
    protected array $replaces = [];

    /**
     * @param string $path
     * @param array<string, string> $replaces
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
     * @param array<string, string> $replaces
     *
     * @return Stub
     */
    public static function create(string $path, array $replaces = []): Stub
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
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get stub path.
     */
    public function getPath(): string
    {
        $customStubsFolderPath = GeneratorHelper::getCustomStubsPath();
        $customStubFilePath = $customStubsFolderPath . '/' . ltrim($this->path, '/');

        if (file_exists($customStubFilePath)) {
            return $customStubFilePath;
        }

        return __DIR__ . '/../../Commands/Generators/stubs/' . ltrim($this->path, '/');
    }

    /**
     * Set replacements array.
     *
     * @param array<string, string> $replaces
     *
     * @return $this
     */
    public function setReplaces(array $replaces = []): static
    {
        $this->replaces = $replaces;

        return $this;
    }

    /**
     * Get replacements.
     *
     * @return array<string, string>
     */
    public function getReplaces(): array
    {
        return $this->replaces;
    }

    /**
     * Render stub contents.
     */
    public function render(): string
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
     * Handle magic method __toString.
     */
    public function __toString()
    {
        return $this->render();
    }
}
