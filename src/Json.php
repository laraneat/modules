<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;

class Json implements Arrayable
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
    protected Collection $attributes;

    public function __construct(string $path, ?Filesystem $filesystem = null, ?array $cachedAttributes = null)
    {
        $this->path = $path;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->attributes = Collection::make($cachedAttributes ?? $this->retrieveRawAttributes());
    }

    /**
     * Make new instance.
     */
    public static function make(string $path, ?Filesystem $filesystem = null, ?array $cachedAttributes = null): static
    {
        return new static($path, $filesystem, $cachedAttributes);
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

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /**
     * Get file contents as array.
     *
     * @throws FileNotFoundException|JsonException
     */
    public function retrieveRawAttributes(): array
    {
        return json_decode($this->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
     * Convert the given array data to pretty json.
     *
     * @throws JsonException
     */
    public function toJsonPretty(): string
    {
        return json_encode($this->attributes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
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
     * Save the current attributes array to the file storage.
     *
     * @throws JsonException
     */
    public function save(): int|false
    {
        return $this->filesystem->put($this->getPath(), $this->toJsonPretty());
    }

    /**
     * Get json attributes as plain array
     */
    #[ArrayShape(['path' => "string", 'attributes' => "array"])]
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'attributes' => $this->attributes->toArray(),
        ];
    }

    /**
     * Get the specified attribute from json file.
     */
    public function get($key, $default = null)
    {
        return $this->attributes->get($key, $default);
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
     * Handle magic method __get.
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Handle magic method __set.
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Handle magic method __isset.
     */
    public function __isset(string $name): bool
    {
        return $this->attributes->offsetExists($name);
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
