<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use JsonException;
use Laraneat\Modules\Exceptions\ComposerFailed;
use stdClass;

/**
 * Edits a composer.json file. The JSON is decoded to objects, so "{}" stays "{}",
 * and written back atomically, only when it changed. A changed file is re-encoded
 * as pretty-printed JSON with its original indentation.
 *
 * @internal
 */
final class ComposerJson
{
    private const string PACKAGIST = 'https://repo.packagist.org';

    /**
     * Composer\Repository\PlatformRepository::PLATFORM_PACKAGE_REGEX
     */
    private const string PLATFORM_PACKAGE = '{^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer(?:-(?:plugin|runtime)-api)?)$}iD';

    private function __construct(
        private readonly string $path,
        private readonly stdClass $data,
        private readonly string $indent,
        private string $saved,
    ) {}

    public static function read(string $path): self
    {
        $json = is_file($path) ? file_get_contents($path) : false;

        if ($json === false) {
            throw ComposerFailed::because("Unable to read [{$path}].");
        }

        try {
            $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ComposerFailed::because("[{$path}] is not valid JSON: {$exception->getMessage()}", $exception);
        }

        if (! $data instanceof stdClass) {
            throw ComposerFailed::because("[{$path}] must contain a JSON object.");
        }

        $indent = preg_match('/^([ \t]+)"/m', $json, $matches) === 1 ? $matches[1] : '    ';

        return new self($path, $data, $indent, self::encode($data, $indent));
    }

    /**
     * A value by its dot path, with objects as arrays.
     */
    public function get(string $key): mixed
    {
        return Arr::get($this->toArray(), $key);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return (array) json_decode((string) json_encode($this->data), true);
    }

    /**
     * Require a module from its path repository, load its dev autoload (tests) from the application
     * and never resolve its vendor from Packagist.
     *
     * @param  string  $repository  The path repository URL, e.g. "modules/*".
     * @param  array<string, string|list<string>>  $autoloadDev  Namespaces and their paths relative to the application.
     */
    public function addModule(string $package, string $repository, array $autoloadDev): self
    {
        $this->addPathRepository($repository);
        $this->excludeFromPackagist($package);

        // A path package has a "dev-*" version, which "minimum-stability": "stable" refuses without "@dev".
        if (! $this->requires($package)) {
            $require = $this->object($this->data, 'require');
            $require->{$package} = '*@dev';

            if ($this->get('config.sort-packages') === true) {
                $this->data->require = $this->sortPackages($require);
            }
        }

        if ($autoloadDev !== []) {
            $psr4 = $this->object($this->object($this->data, 'autoload-dev'), 'psr-4');

            foreach ($autoloadDev as $namespace => $path) {
                $psr4->{$namespace} = $path;
            }
        }

        return $this;
    }

    public function removeRequire(string $package): self
    {
        foreach (['require', 'require-dev'] as $key) {
            if (($this->data->{$key} ?? null) instanceof stdClass) {
                unset($this->data->{$key}->{$package});
            }
        }

        return $this;
    }

    /**
     * @param  list<string>  $namespaces
     */
    public function removeAutoloadDev(array $namespaces): self
    {
        $autoloadDev = $this->data->{'autoload-dev'} ?? null;
        $psr4 = $autoloadDev instanceof stdClass ? ($autoloadDev->{'psr-4'} ?? null) : null;

        if ($psr4 instanceof stdClass) {
            foreach ($namespaces as $namespace) {
                unset($psr4->{$namespace});
            }
        }

        return $this;
    }

    /**
     * Register a service provider for Laravel package discovery.
     */
    public function addProvider(string $provider): self
    {
        $laravel = $this->object($this->object($this->data, 'extra'), 'laravel');
        $providers = is_array($laravel->providers ?? null) ? $laravel->providers : [];

        if (! in_array($provider, $providers, true)) {
            $laravel->providers = [...$providers, $provider];
        }

        return $this;
    }

    /**
     * Whether "require" or "require-dev" has the package.
     */
    public function requires(string $package): bool
    {
        foreach (['require', 'require-dev'] as $key) {
            $require = $this->data->{$key} ?? null;

            if ($require instanceof stdClass && is_string($require->{$package} ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether Composer never looks the package up on Packagist or on a Packagist mirror.
     */
    public function isExcludedFromPackagist(string $package): bool
    {
        [$repositories, $default] = $this->packagist();

        if ($default) {
            return false;
        }

        foreach ($repositories as $repository) {
            if (! $this->excludes($repository, $package)) {
                return false;
            }
        }

        return true;
    }

    public function isDirty(): bool
    {
        return self::encode($this->data, $this->indent) !== $this->saved;
    }

    /**
     * The file content as it will be saved.
     */
    public function contents(): string
    {
        return self::encode($this->data, $this->indent);
    }

    public function save(): void
    {
        if ($this->isDirty()) {
            // Without a mode, replace() makes the file executable.
            $mode = fileperms($this->path);

            (new Filesystem)->replace($this->path, $this->saved = $this->contents(), $mode === false ? null : $mode & 0777);
        }
    }

    private function addPathRepository(string $url): void
    {
        foreach ($this->repositories() as $repository) {
            if ($repository instanceof stdClass && ($repository->type ?? null) === 'path' && ($repository->url ?? null) === $url) {
                return;
            }
        }

        $this->addRepository('modules', (object) ['type' => 'path', 'url' => $url, 'options' => (object) ['symlink' => true]]);
    }

    /**
     * Modules are required with "*@dev" from a path repository. Without this, a missing module
     * directory (another branch, a partial checkout) makes Composer install a package with
     * the same name from Packagist instead of failing.
     */
    private function excludeFromPackagist(string $package): void
    {
        $pattern = strstr($package, '/', true).'/*';
        [$repositories, $default] = $this->packagist();

        if ($default) {
            // Composer replaces the default Packagist repository with a repository of its URL.
            $this->addRepository('packagist.org', (object) ['type' => 'composer', 'url' => self::PACKAGIST, 'exclude' => [$pattern]]);
        }

        foreach ($repositories as $repository) {
            // Composer does not accept "only" and "exclude" together.
            if (is_array($repository->only ?? null)) {
                if ($this->matches($package, $repository->only)) {
                    throw ComposerFailed::because("[{$this->path}] a Packagist repository serves [{$package}] through \"only\": remove the pattern that matches it.");
                }

                continue;
            }

            $exclude = is_array($repository->exclude ?? null) ? $repository->exclude : [];

            if (! in_array($pattern, $exclude, true)) {
                $repository->exclude = [...$exclude, $pattern];
            }
        }
    }

    private function excludes(stdClass $repository, string $package): bool
    {
        if (is_array($repository->only ?? null)) {
            return ! $this->matches($package, $repository->only);
        }

        return $this->matches($package, is_array($repository->exclude ?? null) ? $repository->exclude : []);
    }

    /**
     * The repositories that serve the packages of Packagist, and whether Composer still uses
     * the default Packagist repository. Composer semantics:
     *
     * - any value under the "packagist.org" or "packagist" key replaces the default repository,
     *   and false, or a {"packagist.org": false} entry, disables it;
     * - a composer repository with a packagist.org URL disables the default repository;
     * - a list entry named "packagist.org" or "packagist" (a mirror, as Composer 2.10 writes it)
     *   is used next to the default repository: Composer ignores the name.
     *
     * @return array{list<stdClass>, bool}
     */
    private function packagist(): array
    {
        $repositories = [];
        $default = true;

        foreach ($this->repositories() as $name => $repository) {
            $slot = is_string($name) && in_array($name, ['packagist', 'packagist.org'], true);

            if ($slot) {
                $default = false;
            }

            if (! $repository instanceof stdClass) {
                continue;
            }

            if (($repository->{'packagist.org'} ?? null) === false || ($repository->packagist ?? null) === false) {
                $default = false;

                continue;
            }

            if (($repository->type ?? null) !== 'composer') {
                continue;
            }

            $url = is_string($repository->url ?? null) && preg_match('{^https?://(?:[a-z0-9-.]+\.)?packagist\.org(/|$)}', $repository->url) === 1;

            if ($url) {
                $default = false;
            }

            if ($slot || $url || (is_int($name) && in_array($repository->name ?? null, ['packagist', 'packagist.org'], true))) {
                $repositories[] = $repository;
            }
        }

        return [$repositories, $default];
    }

    /**
     * @param  array<array-key, mixed>  $patterns  Package names with "*" wildcards.
     */
    private function matches(string $package, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (is_string($pattern) && preg_match('{^'.str_replace('\\*', '.*', preg_quote($pattern)).'$}iD', $package) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function repositories(): array
    {
        $repositories = $this->data->repositories ?? [];

        return $repositories instanceof stdClass ? get_object_vars($repositories) : (array) $repositories;
    }

    /**
     * Append a repository to the list, or add it by name when repositories are an object.
     */
    private function addRepository(string $name, stdClass $repository): void
    {
        $repositories = $this->data->repositories ?? [];

        if ($repositories instanceof stdClass) {
            $key = $name;

            for ($i = 2; property_exists($repositories, $key); $i++) {
                $key = $name.'-'.$i;
            }

            $repositories->{$key} = $repository;

            return;
        }

        if (! is_array($repositories)) {
            throw ComposerFailed::because("[{$this->path}] \"repositories\" must be an array or an object.");
        }

        $this->data->repositories = [...$repositories, $repository];
    }

    private function object(stdClass $parent, string $key): stdClass
    {
        $value = $parent->{$key} ?? null;

        if ($value instanceof stdClass) {
            return $value;
        }

        if ($value !== null && $value !== []) {
            throw ComposerFailed::because("[{$this->path}] \"{$key}\" must be an object.");
        }

        return $parent->{$key} = new stdClass;
    }

    /**
     * Composer's "sort-packages" order: platform packages first (PHP, HHVM, extensions, libraries,
     * then the others), then packages by name. It is the order of Composer's JsonManipulator.
     */
    private function sortPackages(stdClass $packages): stdClass
    {
        $packages = get_object_vars($packages);

        $weight = static fn (string $name): string => preg_match(self::PLATFORM_PACKAGE, $name) === 1
            ? (string) preg_replace(['/^php/', '/^hhvm/', '/^ext/', '/^lib/', '/^\D/'], ['0-$0', '1-$0', '2-$0', '3-$0', '4-$0'], $name)
            : '5-'.$name;

        uksort($packages, static fn (string $a, string $b): int => strnatcmp($weight($a), $weight($b)));

        return (object) $packages;
    }

    private static function encode(stdClass $data, string $indent): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($indent !== '    ') {
            $json = (string) preg_replace_callback('/^(?: {4})+/m', static fn (array $matches): string => str_repeat($indent, intdiv(strlen($matches[0]), 4)), $json);
        }

        return $json."\n";
    }
}
