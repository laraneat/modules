<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

/**
 * The packages Composer installed (vendor/composer/installed.json).
 *
 * @internal
 */
final readonly class InstalledPackages
{
    /**
     * @param  array<string, array<array-key, mixed>>  $packages
     */
    private function __construct(
        private string $vendorPath,
        private array $packages,
    ) {}

    public static function read(string $vendorPath): self
    {
        $json = is_file($file = $vendorPath.'/composer/installed.json') ? json_decode((string) file_get_contents($file), true) : null;
        $packages = [];

        foreach ((array) (is_array($json) && isset($json['packages']) ? $json['packages'] : $json) as $package) {
            if (is_array($package) && is_string($package['name'] ?? null)) {
                $packages[$package['name']] = $package;
            }
        }

        return new self($vendorPath, $packages);
    }

    public function has(string $package): bool
    {
        return isset($this->packages[$package]);
    }

    /**
     * Whether the installed copy of the package metadata matches its composer.json:
     * a changed "require", "autoload" or "extra" (providers) needs "composer update".
     *
     * @param  array<array-key, mixed>  $composerJson
     */
    public function isCurrent(string $package, array $composerJson): bool
    {
        $installed = $this->packages[$package] ?? null;

        if ($installed === null) {
            return false;
        }

        // Composer lowercases the package names of the installed requirements.
        $require = is_array($composerJson['require'] ?? null) ? array_change_key_case($composerJson['require']) : [];

        return ($installed['require'] ?? []) == $require
            && ($installed['autoload'] ?? []) == ($composerJson['autoload'] ?? [])
            && ($installed['extra'] ?? []) == ($composerJson['extra'] ?? []);
    }

    /**
     * The directory the package is installed in.
     */
    public function path(string $package): string
    {
        return $this->vendorPath.'/'.$package;
    }
}
