<?php

namespace Laraneat\Modules\Support;

use Composer\Json\JsonFile;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class ComposerJsonFile
{
    protected Filesystem $filesystem;

    protected JsonFile $composerJsonHandler;

    protected ?array $jsonContent = null;

    public function __construct(
        string $filePath
    ) {
        $this->composerJsonHandler = new JsonFile($filePath);
    }

    public static function create(
        string $filePath
    ): static {
        return new static($filePath);
    }

    /**
     * Get an item from a json content using "dot" notation.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->read(), $key, $default);
    }

    /**
     * Set JSON content item to a given value using "dot" notation.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set(string $key, mixed $value): static
    {
        $this->read();
        Arr::set($this->jsonContent, $key, $value);

        return $this;
    }

    public function addModule(string $modulePackageName, string $moduleRelativePath): static
    {
        $this->addPathRepository(dirname($moduleRelativePath));
        $this->excludeFromPackagist(Str::before($modulePackageName, '/') . '/*');

        $packages = $this->get('require');

        if (! isset($packages[$modulePackageName])) {
            $packages[$modulePackageName] = '*';
            $this->set('require', $this->sortPackages($packages));
        }

        return $this;
    }

    public function addPathRepository(string $path, array $options = ['symlink' => true]): static
    {
        $path = Str::finish(GeneratorHelper::normalizePath($path, true), '/*');

        $repositories = $this->get('repositories', []);
        $repositoryAlreadyExists = collect($repositories)
            ->contains(fn ($repository) => is_array($repository) && ($repository['url'] ?? null) === $path);

        if ($repositoryAlreadyExists) {
            return $this;
        }

        $repositories[] = [
            'type' => 'path',
            'url' => $path,
            'options' => $options,
        ];

        $this->set('repositories', $repositories);

        return $this;
    }

    /**
     * Never resolve the given package pattern (e.g. "app/*") from Packagist.
     *
     * Modules are required with "*" from a path repository. If a module directory is missing
     * (deleted module, other branch, CI without the modules directory), Composer would otherwise
     * fall back to Packagist and could install a foreign package with the same name.
     */
    public function excludeFromPackagist(string $packagePattern): static
    {
        $repositories = $this->get('repositories', []);

        foreach ($repositories as $key => $repository) {
            if (is_array($repository) && $this->isPackagistRepository($repository)) {
                if (! isset($repository['only']) && ! in_array($packagePattern, $repository['exclude'] ?? [], true)) {
                    $repositories[$key]['exclude'] = [...($repository['exclude'] ?? []), $packagePattern];
                    $this->set('repositories', $repositories);
                }

                return $this;
            }
        }

        if ($this->isPackagistDisabled($repositories)) {
            return $this;
        }

        $packagist = ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => [$packagePattern]];

        if (array_is_list($repositories)) {
            $repositories[] = $packagist;
            $repositories[] = ['packagist.org' => false];
        } else {
            $repositories['packagist'] = $packagist;
            $repositories['packagist.org'] = false;
        }

        $this->set('repositories', $repositories);

        return $this;
    }

    /**
     * @param array<array-key, mixed> $repository
     */
    protected function isPackagistRepository(array $repository): bool
    {
        return ($repository['type'] ?? null) === 'composer'
            && in_array(rtrim((string) ($repository['url'] ?? ''), '/'), ['https://repo.packagist.org', 'https://packagist.org'], true);
    }

    /**
     * @param array<array-key, mixed> $repositories
     */
    protected function isPackagistDisabled(array $repositories): bool
    {
        foreach ($repositories as $key => $repository) {
            if (($key === 'packagist.org' && $repository === false)
                || (is_array($repository) && ($repository['packagist.org'] ?? null) === false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save the JSON content to a file
     *
     * @throws \Exception
     */
    public function save(int $flags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): void
    {
        if ($this->jsonContent !== null) {
            $this->composerJsonHandler->write($this->jsonContent, $flags);
        }
    }

    protected function read(): array
    {
        if ($this->jsonContent !== null) {
            return $this->jsonContent;
        }

        return $this->jsonContent = $this->composerJsonHandler->read();
    }

    /**
     * @param array<string, string> $packages
     * @return array<string, string>
     */
    protected function sortPackages(array $packages): array
    {
        $prefix = fn ($requirement)
            => preg_replace(
                [
                    '/^php$/',
                    '/^hhvm-/',
                    '/^ext-/',
                    '/^lib-/',
                    '/^\D/',
                    '/^(?!php$|hhvm-|ext-|lib-)/',
                ],
                [
                    '0-$0',
                    '1-$0',
                    '2-$0',
                    '3-$0',
                    '4-$0',
                    '5-$0',
                ],
                $requirement
            );

        uksort($packages, function ($a, $b) use ($prefix) {
            return strnatcmp($prefix($a), $prefix($b));
        });

        return $packages;
    }
}
