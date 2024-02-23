<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleType;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Symfony\Component\Console\Exception\InvalidOptionException;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

abstract class BaseCommand extends Command
{
    public function __construct(protected ModulesRepository $modulesRepository)
    {
        parent::__construct();
    }

    /**
     * @return array<int, Module>|Module
     *
     * @throws ModuleNotFound
     * @throws ModuleHasNonUniquePackageName
     */
    protected function getModuleArgumentOrFail(ModuleType $type = ModuleType::All): array|Module
    {
        $allPackageNames = array_keys($this->modulesRepository->getModules($type));
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
     * @throws ModuleNotFound
     * @throws ModuleHasNonUniquePackageName
     */
    protected function findModuleByNameOrPackageNameOrFail($moduleNameOrPackageName, ModuleType $type = ModuleType::All): Module
    {
        if ($foundModule = $this->modulesRepository->find($moduleNameOrPackageName, $type)) {
            return $foundModule;
        }

        $foundModules = collect($this->modulesRepository->getModules($type))
            ->filter(fn (Module $module) => Str::lower($module->getName()) === Str::lower($moduleNameOrPackageName));

        $numberOfFoundModules = $foundModules->count();

        if ($numberOfFoundModules === 0) {
            throw ModuleNotFound::makeForNameOrPackageName($moduleNameOrPackageName);
        }

        if ($numberOfFoundModules === 1) {
            return $foundModules->first();
        }

        $selectedPackageName = $this->choice(
            "$numberOfFoundModules modules with name '{$moduleNameOrPackageName}' found, please select one module from those found",
            $foundModules->keys()->all(),
        );


        return $this->modulesRepository->findOrFail($selectedPackageName, $type);
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
    ): ?string
    {
        $value = $this->option($optionName);

        if ($value === '' || $value === null) {
            $value = trim($this->ask($question, $default));
        }

        if ($required && $value === '') {
            throw new InvalidOptionException(
                sprintf("The '%s' option is required", $optionName)
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
    ): ?string
    {
        $value = $this->option($optionName);

        if ($value === '' || $value === null) {
            $value = $this->choice($question, $choices, $default);
        } elseif (!in_array($value, $choices, true)) {
            throw new InvalidOptionException(
                sprintf(
                    "Wrong '%s' option value provided. Value should be one of '%s'.",
                    $optionName,
                    implode("' or '", $choices)
                )
            );
        }

        return $value;
    }

    /**
     * Checks if the option is set (via CLI)
     *
     * @throws InvalidOptionException
     */
    protected function getOptionOneOf(
        string $optionName,
        array $choices,
        ?string $default = null
    ): ?string
    {
        $value = $this->option($optionName) ?: $default;

        if (!in_array($value, $choices, true)) {
            throw new InvalidOptionException(
                sprintf(
                    "Wrong '%s' option value provided. Value should be one of '%s'.",
                    $optionName,
                    implode("' or '", $choices)
                )
            );
        }

        return $value;
    }
}
