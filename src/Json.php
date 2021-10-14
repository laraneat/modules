<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use JsonException;

class Json
{
    /**
     * The file path.
     */
    protected string $path;

    /**
     * The laravel filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * The collection of attributes.
     */
    protected \Illuminate\Support\Collection $attributes;

    /**
     * The constructor.
     */
    public function __construct(string $path, ?Filesystem $filesystem = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->attributes = Collection::make($this->getAttributes());
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

    public function setPath($path): static
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Make new instance.
     */
    public static function make(string $path, ?Filesystem $filesystem = null): static
    {
        return new static($path, $filesystem);
    }

    /**
     * Get file content.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function getContents(): string
    {
        return $this->filesystem->get($this->getPath());
    }

    /**
     * Get file contents as array.
     *
     * @throws FileNotFoundException|JsonException
     */
    public function getAttributes(): array
    {
        return json_decode($this->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the given array data to pretty json.
     *
     * @throws JsonException
     */
    public function toJsonPretty(?array $data = null): string
    {
        return json_encode($data ?: $this->attributes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Update json contents from array data.
     *
     * @throws FileNotFoundException|JsonException
     */
    public function update(array $data): int|false
    {
        $this->attributes = new Collection(array_merge($this->attributes->toArray(), $data));

        return $this->save();
    }

    /**
     * Handle magic method __set.
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->attributes->offsetExists($name);
    }

    /**
     * Set a specific key & value.
     */
    public function set($key, $value): static
    {
        $this->attributes->offsetSet($key, $value);

        return $this;
    }

    /**
     * Save the current attributes array to the file storage.
     *
     * @throws JsonException
     */
    public function save(): int|false
    {
        return $this->filesystem->put($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Handle magic method __get.
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Get the specified attribute from json file.
     */
    public function get($key, $default = null)
    {
        return $this->attributes->get($key, $default);
    }

    /**
     * Handle call to __call method.
     */
    public function __call(string $method, array $arguments = [])
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arguments);
        }

        return call_user_func_array([$this->attributes, $method], $arguments);
    }

    /**
     * Handle call to __toString method.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function __toString()
    {
        return $this->getContents();
    }
}
