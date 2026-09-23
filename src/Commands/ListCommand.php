<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Laraneat\Modules\Manifest\ManifestBuilder;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @phpstan-import-type ModuleManifest from ManifestBuilder
 */
#[AsCommand(name: 'module:list', description: 'List the modules; with -v, also what is loaded from them')]
final class ListCommand extends Command
{
    protected $signature = 'module:list';

    public function handle(ModuleRepository $modules, ApplicationComposer $composer): int
    {
        $manifest = $modules->manifest();

        if ($manifest === []) {
            $this->components->warn("No modules found in [{$composer->relative($modules->modulesPath())}].");

            return self::SUCCESS;
        }

        $verbose = $this->output->isVerbose();
        $rows = [];

        foreach ($manifest as $name => $module) {
            $row = [$name, $module['package'], $module['namespace'], $composer->relative($module['path'])];

            if ($verbose) {
                $row[] = $this->resources($module);
            }

            $rows[] = $row;
        }

        $this->table(['Name', 'Package', 'Namespace', 'Path', ...($verbose ? ['Loads'] : [])], $rows);

        return self::SUCCESS;
    }

    /**
     * @param  ModuleManifest  $module
     */
    private function resources(array $module): string
    {
        $seeders = count(self::concrete(array_merge(...array_values($module['seeders'])), Seeder::class));
        $commands = count(self::concrete($module['commands'], SymfonyCommand::class));

        return implode(', ', array_filter([
            $module['config'] === [] ? null : 'config ('.implode(', ', $module['config']).')',
            $module['lang'] ? ($module['json'] ? 'lang (+json)' : 'lang') : null,
            $module['views'] ? 'views' : null,
            $module['migrations'] ? 'migrations' : null,
            ...array_map(
                static fn (string $group, array $files): string => "{$group} routes (".array_sum(array_map(count(...), $files)).')',
                array_keys($module['routes']),
                $module['routes'],
            ),
            $seeders === 0 ? null : "seeders ({$seeders})",
            $commands === 0 ? null : "commands ({$commands})",
        ])) ?: '-';
    }

    /**
     * The manifest lists every class of the directory; only those that are loaded count.
     *
     * @param  list<string>  $classes
     * @param  class-string  $parent
     * @return list<string>
     */
    private static function concrete(array $classes, string $parent): array
    {
        return array_values(array_filter(
            $classes,
            static fn (string $class): bool => is_subclass_of($class, $parent) && ! (new ReflectionClass($class))->isAbstract(),
        ));
    }
}
