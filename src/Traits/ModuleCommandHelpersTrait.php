<?php

namespace Laraneat\Modules\Traits;

use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleTypeEnum;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Symfony\Component\Console\Exception\InvalidOptionException;
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
     * @return array<int, Module>|Module
     *
     * @throws ModuleNotFoundException
     * @throws ModuleHasNonUniquePackageName
     */
    protected function getModuleArgumentOrFail(ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): array|Module
    {
        $allPackageNames = array_keys($this->getModulesRepository()->getModules($typeEnum));
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
            ->unique(fn (Module $module) => $module->getPackageName())
            ->values()
            ->all();
    }

    /**
     * @throws ModuleNotFoundException
     * @throws ModuleHasNonUniquePackageName
     */
    protected function findModuleByNameOrPackageNameOrFail($moduleNameOrPackageName, ModuleTypeEnum $typeEnum = ModuleTypeEnum::All): Module
    {
        if ($foundModule = $this->getModulesRepository()->find($moduleNameOrPackageName, $typeEnum)) {
            return $foundModule;
        }

        $foundModules = collect($this->getModulesRepository()->getModules($typeEnum))
            ->filter(fn (Module $module) => Str::lower($module->getName()) === Str::lower($moduleNameOrPackageName));

        $numberOfFoundModules = $foundModules->count();

        if ($numberOfFoundModules === 0) {
            throw ModuleNotFoundException::makeForNameOrPackageName($moduleNameOrPackageName);
        }

        if ($numberOfFoundModules === 1) {
            return $foundModules->first();
        }

        $selectedPackageName = $this->choice(
            "$numberOfFoundModules modules with name «{$moduleNameOrPackageName}» found, please select one module from those found",
            $foundModules->keys()->all(),
        );


        return $this->getModulesRepository()->findOrFail($selectedPackageName, $typeEnum);
    }

    /**
     * Checks if the option is set (via CLI), otherwise asks the user for a value
     *
     * @throws InvalidOptionException
     */
    protected function getOptionOrAsk(
        string $optionName,
        string $question,
        ?string $default = null,
        bool $required = true
    ): string
    {
        $value = $this->option($optionName);

        if ($value === '' || $value === null) {
            $value = trim($this->ask($question, $default));
        }

        if ($required && ($value === '' || $value === null)) {
            throw new InvalidOptionException(
                sprintf("The «%s» option is required", $optionName)
            );
        }

        return $value;
    }

    /**
     * Checks if the option is set (via CLI), otherwise proposes choices to the user
     *
     * @throws InvalidOptionException
     */
    protected function getOptionOrChoice(
        string $optionName,
        string $question,
        array $choices,
        ?string $default = null
    ): string
    {
        $value = $this->option($optionName);

        if ($value === '' || $value === null) {
            $value = $this->choice($question, $choices, $default);
        } elseif (!in_array($value, $choices, true)) {
            throw new InvalidOptionException(
                sprintf(
                    "Wrong «%s» option value provided. Value should be one of «%s».",
                    $optionName,
                    implode('» or «', $choices)
                )
            );
        }

        return $value;
    }
}
