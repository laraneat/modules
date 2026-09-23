<?php

declare(strict_types=1);

namespace Laraneat\Modules\Registrars;

use Illuminate\Routing\Router;
use Laraneat\Modules\Manifest\ManifestBuilder;

/**
 * Loads the route files of the modules into the route groups of the config.
 * Nested directories are appended to the group prefix.
 *
 * @internal
 *
 * @phpstan-import-type Manifest from ManifestBuilder
 */
final readonly class RouteRegistrar
{
    /**
     * @param  array<array-key, mixed>  $groups  The "routes" config.
     * @param  Manifest  $manifest
     */
    public function __construct(
        private Router $router,
        private array $groups,
        private array $manifest,
    ) {}

    public function register(): void
    {
        foreach ($this->groups as $group => $attributes) {
            if (! is_array($attributes)) {
                continue;
            }

            unset($attributes['path']);
            $prefix = is_string($attributes['prefix'] ?? null) ? trim($attributes['prefix'], '/') : '';

            foreach ($this->manifest as $module) {
                foreach ($module['routes'][$group] ?? [] as $directory => $files) {
                    $directoryAttributes = $directory === ''
                        ? $attributes
                        : ['prefix' => ltrim($prefix.'/'.$directory, '/')] + $attributes;

                    foreach ($files as $file) {
                        $this->router->group($directoryAttributes, $module['path'].'/'.$file);
                    }
                }
            }
        }
    }
}
