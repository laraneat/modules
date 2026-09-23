<?php

namespace Laraneat\Modules\Support\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Facades\Modules;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

/**
 * This trait helps to run seeders from modules
 *
 * @mixin \Illuminate\Database\Seeder
 */
trait CanRunModuleSeeders
{
    public function runSeedersFromModules(array|string $subdirectories = []): void
    {
        $subdirectories = Arr::wrap($subdirectories);
        $modules = Modules::getModules();

        $seederClasses = [];
        foreach ($modules as $module) {
            $seederClasses[] = $this->getSeederClassesFromModule($module, $subdirectories);
        }

        $seederClasses = $this->sortSeederClasses(Arr::flatten($seederClasses));
        foreach ($seederClasses as $seeder) {
            $this->call($seeder);
        }
    }

    /**
     * Seeders run in the order of their integer "_N" suffix (e.g. "PermissionsSeeder_1"),
     * seeders without such a suffix run last; ties are ordered by class name.
     */
    protected function sortSeederClasses(array $seedersClasses): array
    {
        $order = static function (string $seederClass): int {
            $suffix = Str::afterLast(class_basename($seederClass), '_');

            return $suffix !== class_basename($seederClass) && ctype_digit($suffix) ? (int) $suffix : PHP_INT_MAX;
        };

        usort($seedersClasses, static fn (string $a, string $b): int => [$order($a), $a] <=> [$order($b), $b]);

        return $seedersClasses;
    }

    protected function getSeederClassesFromModule(Module $module, array $subdirectories = []): array
    {
        $moduleSeedersPath = rtrim(GeneratorHelper::component(ModuleComponentType::Seeder)->getFullPath($module), '/');
        $paths = array_unique([
            $moduleSeedersPath,
            ...array_map(
                static fn ($subdirectory) => rtrim($moduleSeedersPath . '/' . trim($subdirectory, '/'), '/'),
                $subdirectories
            ),
        ]);

        $seederClasses = [];
        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                foreach (File::files($path) as $file) {
                    if ($file->getExtension() === 'php') {
                        $seederClasses[] = $this->getClassFullNameFromFile($file->getPathname());
                    }
                }
            }
        }

        return $seederClasses;
    }

    /**
     * Get the full name (name \ namespace) of a class from its file path
     * result example: (string) "I\Am\The\Namespace\Of\This\Class"
     *
     * @param string $filePathName
     *
     * @return string
     */
    public function getClassFullNameFromFile(string $filePathName): string
    {
        return $this->getClassNamespaceFromFile($filePathName) . '\\' . $this->getClassNameFromFile($filePathName);
    }

    /**
     * Get the class namespace form file path using token
     *
     * @param string $filePathName
     *
     * @return null|string
     */
    protected function getClassNamespaceFromFile(string $filePathName): ?string
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);

                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }

                break;
            }
            $i++;
        }
        if (! $namespace_ok) {
            return null;
        }

        return $namespace;
    }

    /**
     * Get the class name from file path using token
     *
     * @param string $filePathName
     *
     * @return string
     */
    protected function getClassNameFromFile(string $filePathName): string
    {
        $php_code = file_get_contents($filePathName);

        $classes = [];
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] === T_CLASS
                && $tokens[$i - 1][0] === T_WHITESPACE
                && $tokens[$i][0] === T_STRING
            ) {
                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes[0];
    }
}
