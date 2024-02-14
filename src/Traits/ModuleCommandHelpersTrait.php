<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

/**
 * @mixin \Illuminate\Console\Command
 */
trait ModuleCommandHelpersTrait
{
    protected function getModulesRepository(): ModulesRepository
    {
        return $this->laravel[ModulesRepository::class];
    }

    /**
     * @return array<string, Module>|Module
     *
     * @throws ModuleNotFoundException
     * @throws ModuleHasNonUniquePackageName
     */
    protected function getModuleArgumentOrFail(): array|Module
    {
        $allPackageNames = array_keys($this->getModulesRepository()->getModules());
        $moduleArgument = $this->input->getArgument('module');
        $multipleModuleMode = is_array($moduleArgument);

        if ($multipleModuleMode) {
            $moduleArgument = array_values(array_unique($moduleArgument));

            if (empty($moduleArgument)) {
                $moduleArgument = multiselect(
                    label: 'Select one or more module',
                    options: [
                        'all' => 'All modules',
                        ...array_combine($allPackageNames, $allPackageNames)
                    ],
                    required: 'You must select at least one module',
                );
            }

            if (in_array('all', $moduleArgument, true)) {
                $moduleArgument = $allPackageNames;
            }
        } else {
            if (empty($moduleArgument)) {
                $moduleArgument = select(
                    label: 'Select one module',
                    options: $allPackageNames,
                    required: 'You must select a module',
                );
            }
        }

        $this->input->setArgument('module', value: $moduleArgument);

        if (!$multipleModuleMode) {
            return $this->findModuleByNameOrPackageNameOrFail($moduleArgument);
        }

        return collect($moduleArgument)
            ->map(fn (string $moduleNameOrPackageName)
            => $this->findModuleByNameOrPackageNameOrFail($moduleNameOrPackageName))
            ->all();
    }

    /**
     * @throws ModuleNotFoundException
     * @throws ModuleHasNonUniquePackageName
     */
    protected function findModuleByNameOrPackageNameOrFail($moduleNameOrPackageName): Module
    {
        if ($foundModule = $this->getModulesRepository()->find($moduleNameOrPackageName)) {
            return $foundModule;
        }

        $foundModules = collect($this->getModulesRepository()->getModules())
            ->filter(fn (Module $module) => Str::lower($module->getName()) === Str::lower($moduleNameOrPackageName));

        $numberOfFoundModules = $foundModules->count();

        if ($numberOfFoundModules === 0) {
            throw ModuleNotFoundException::makeForName($moduleNameOrPackageName);
        }

        if ($numberOfFoundModules === 1) {
            return $foundModules->first();
        }

        $selectedPackageName = $this->choice(
            "$numberOfFoundModules modules with name «{$moduleNameOrPackageName}» found, please select one module from those found",
            $foundModules->keys()->all(),
        );

        return $this->getModulesRepository()->findOrFail($selectedPackageName);
    }
}
