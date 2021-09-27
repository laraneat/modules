<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

/**
 * This trait helps to run seeders from modules
 *
 * @mixin \Illuminate\Database\Seeder
 */
trait SeederLoaderTrait
{
    public function runSeedersFromModules(): void
    {
        $modules = Modules::allEnabled();

        $seederClasses = [];
        foreach ($modules as $module) {
            $seederClasses[] = $this->getSeederClassesFromModule($module);
        }

        $seederClasses = $this->sortSeederClasses(Arr::flatten($seederClasses));
        foreach ($seederClasses as $seeder) {
            $this->call($seeder);
        }
    }

    protected function sortSeederClasses(array $seedersClasses): array
    {
        return Arr::sort($seedersClasses, function (string $seederClass) {
            $baseClass = class_basename($seederClass);

            if (Str::contains($baseClass, '_')) {
                return Str::afterLast($baseClass, '_');
            }

            return 'z';
        });
    }

    protected function getSeederClassesFromModule(Module $module): array
    {
        $moduleSeedersPath = GeneratorHelper::component('seeder')->getFullPath($module);

        $seederClasses = [];
        if (File::isDirectory($moduleSeedersPath)) {
            $allFiles = File::allFiles($moduleSeedersPath);

            foreach ($allFiles as $file) {
                $seederClasses[] = $this->getClassFullNameFromFile($file->getPathname());
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
        if (!$namespace_ok) {
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
