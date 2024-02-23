<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class ModuleMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make
                            {name : The name of the module to be created}
                            {--preset= : The preset of the module to be created ("plain", "base", or "api", "plain" by default)}
                            {--entity= : Entity name (used to create module components, the default is the name of the module)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module.';

    /**
     * The name of the module to be created
     */
    protected string $moduleName;

    /**
     * The package name of the module to be created
     */
    protected string $modulePackageName;

    /**
     * The preset of the module to be created
     */
    protected string $modulePreset;

    /**
     * The entity name (used to create module components, the default is the name of the module)
     */
    protected ?string $entityName = null;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the name of the module to be created',
        ];
    }

    /**
     * Execute the console command.
     *
     * @param ModulesRepository $modulesRepository
     *
     * @return int
     */
    public function handle(ModulesRepository $modulesRepository): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        if (!$this->validateModuleName($this->moduleName)) {
            $this->components->error("Module name can only consist of letters!");

            return self::FAILURE;
        }

        $this->modulePackageName = sprintf(
            '%s/%s',
            Str::kebab(config('modules.generator.composer.vendor', 'app')),
            Str::kebab(Str::after($this->moduleName, '/'))
        );

        if ($modulesRepository->has($this->modulePackageName)) {
            $this->components->error("Module '$this->modulePackageName' already exist!");

            return self::FAILURE;
        }

        $this->modulePreset = $this->getOptionOrChoice(
            optionName: 'preset',
            question: 'Select the preset of module to create',
            choices: [
                'plain',
                'base',
                'api'
            ],
            default: 'plain'
        );

        if ($this->isFailure($this->generateComposerJsonFile())) {
            return self::FAILURE;
        }

        $this->modulesRepository->pruneAppModulesManifest();

        if ($this->isFailure($this->generateComponents($this->modulesRepository->find($this->modulePackageName)))) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function generateComposerJsonFile(): int
    {
        $path = GeneratorHelper::makeModulePath($this->moduleName, 'composer.json');
        $contents = Stub::create("composer.json.stub", [
            'modulePackageName' => $this->modulePackageName,
            'moduleName' => $this->moduleName,
            'moduleNamespace' => str_replace(
                '\\',
                '\\\\',
                GeneratorHelper::makeModuleNamespace($this->moduleName)
            ),
            'authorName' => config('modules.generator.composer.author.name', 'Example'),
            'authorEmail' => config('modules.generator.composer.author.email', 'example@example.com'),
        ])->render();

        return $this->generate($path, $contents);
    }

    protected function generateComponents(Module $module): int
    {
        $statuses = [$this->generateProviders($module)];

        if ($this->modulePreset !== 'plain') {
            $statuses[] = $this->generateBaseComponents($module);
        }

        if ($this->modulePreset === 'api') {
            $statuses[] = $this->generateApiComponents($module);
        }

        return $this->isFailure(...$statuses) ? self::FAILURE : self::SUCCESS;
    }

    protected function generateBaseComponents(Module $module): int
    {
        $statuses = [];
        $modulePackageName = $module->getPackageName();
        $entityName = $this->getEntityName();
        $snakeEntityName = Str::snake($entityName);
        $snakePluralEntityName = Str::plural($snakeEntityName);

        if (GeneratorHelper::component(ModuleComponentType::Factory)->generate()) {
            $statuses[] = $this->call('module:make:factory', [
                'name' => "{$entityName}Factory",
                'module' => $modulePackageName,
                '--model' => $entityName
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::Migration)->generate()) {
            $statuses[] = $this->call('module:make:migration', [
                'name' => "create_{$snakePluralEntityName}_table",
                'module' => $modulePackageName,
                '--stub' => 'create'
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::Seeder)->generate()) {
            $statuses[] = $this->call('module:make:seeder', [
                'name' => "{$entityName}PermissionsSeeder_1",
                'module' => $modulePackageName,
                '--stub' => 'permissions',
                '--model' => $entityName
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::Dto)->generate()) {
            $statuses[] = $this->call('module:make:dto', [
                'name' => "Create{$entityName}DTO",
                'module' => $modulePackageName,
            ]);
            $statuses[] = $this->call('module:make:dto', [
                'name' => "Update{$entityName}DTO",
                'module' => $modulePackageName,
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::Model)->generate()) {
            $statuses[] = $this->call('module:make:model', [
                'name' => $entityName,
                'module' => $modulePackageName,
                '--factory' => "{$entityName}Factory"
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::Policy)->generate()) {
            $statuses[] = $this->call('module:make:policy', [
                'name' => "{$entityName}Policy",
                'module' => $modulePackageName,
                '--model' => $entityName
            ]);
        }

        return $this->isFailure(...$statuses) ? self::FAILURE : self::SUCCESS;
    }

    protected function generateApiComponents(Module $module): int
    {
        $statuses = [];
        $actionVerbs = ['create', 'update', 'delete', 'list', 'view'];

        $modulePackageName = $module->getPackageName();
        $entityName = $this->getEntityName();
        $pluralEntityName = Str::plural($entityName);
        $camelEntityName = Str::camel($entityName);
        $kebabPluralEntityName = Str::kebab($pluralEntityName);
        $snakePluralEntityName = Str::snake($pluralEntityName);

        if (GeneratorHelper::component(ModuleComponentType::ApiQueryWizard)->generate()) {
            $statuses[] = $this->call('module:make:query-wizard', [
                'name' => "{$pluralEntityName}QueryWizard",
                'module' => $modulePackageName,
                '--stub' => 'eloquent',
            ]);
            $statuses[] = $this->call('module:make:query-wizard', [
                'name' => "{$entityName}QueryWizard",
                'module' => $modulePackageName,
                '--stub' => 'model',
            ]);
        }

        if (GeneratorHelper::component(ModuleComponentType::ApiResource)->generate()) {
            $statuses[] = $this->call('module:make:resource', [
                'name' => "{$entityName}Resource",
                'module' => $modulePackageName,
                '--stub' => 'single'
            ]);
        }

        foreach ($actionVerbs as $actionVerb) {
            $studlyActionVerb = Str::studly($actionVerb);

            $resourceClass = "{$entityName}Resource";
            $dtoClass = "{$studlyActionVerb}{$entityName}DTO";
            $routeName = 'api.' . $snakePluralEntityName . '.' . $actionVerb;

            if ($actionVerb === "list") {
                $actionClass = "{$studlyActionVerb}{$pluralEntityName}Action";
                $requestClass = "{$studlyActionVerb}{$pluralEntityName}Request";
                $wizardClass = "{$pluralEntityName}QueryWizard";
            } else {
                $actionClass = "{$studlyActionVerb}{$entityName}Action";
                $requestClass = "{$studlyActionVerb}{$entityName}Request";
                $wizardClass = "{$entityName}QueryWizard";
            }

            if (GeneratorHelper::component(ModuleComponentType::Action)->generate()) {
                $statuses[] = $this->call('module:make:action', [
                    'name' => $actionClass,
                    'module' => $modulePackageName,
                    '--stub' => $actionVerb,
                    '--dto' => $dtoClass,
                    '--model' => $entityName,
                    '--request' => $requestClass,
                    '--resource' => $resourceClass,
                    '--wizard' => $wizardClass
                ]);
            }

            if (GeneratorHelper::component(ModuleComponentType::ApiRequest)->generate()) {
                $statuses[] = $this->call('module:make:request', [
                    'name' => $requestClass,
                    'module' => $modulePackageName,
                    '--stub' => $actionVerb,
                    '--ui' => 'api',
                    '--dto' => $dtoClass,
                    '--model' => $entityName,
                ]);
            }

            if (GeneratorHelper::component(ModuleComponentType::ApiRoute)->generate()) {
                $actionMethodsMap = [
                    'create' => 'post',
                    'update' => 'patch',
                    'delete' => 'delete',
                    'list' => 'get',
                    'view' => 'get'
                ];

                $url = $kebabPluralEntityName;
                if (in_array($actionVerb, ['update', 'delete', 'view'])) {
                    $url .= '/{' . $camelEntityName . '}';
                }

                $filePath = Str::snake(str_replace('Action', '', $actionClass), '_');
                $filePath = 'v1/' . $filePath;

                $statuses[] = $this->call('module:make:route', [
                    'name' => $filePath,
                    'module' => $modulePackageName,
                    '--ui' => 'api',
                    '--action' => $actionClass,
                    '--method' => $actionMethodsMap[$actionVerb],
                    '--url' => $url,
                    '--name' => $routeName
                ]);
            }

            if (GeneratorHelper::component(ModuleComponentType::ApiTest)->generate()) {
                $testClass = $actionVerb === 'list'
                    ? "{$studlyActionVerb}{$pluralEntityName}Test"
                    : "{$studlyActionVerb}{$entityName}Test";

                $statuses[] = $this->call('module:make:test', [
                    'name' => $testClass,
                    'module' => $modulePackageName,
                    '--stub' => $actionVerb,
                    '--type' => 'api',
                    '--model' => $entityName,
                    '--route' => $routeName
                ]);
            }
        }

        return $this->isFailure(...$statuses) ? self::FAILURE : self::SUCCESS;
    }

    protected function generateProviders(Module $module): int
    {
        if (!GeneratorHelper::component(ModuleComponentType::Provider)->generate()) {
            return self::SUCCESS;
        }

        return $this->isFailure(
            $this->call('module:make:provider', [
                'name' => "{$module->getStudlyName()}ServiceProvider",
                'module' => $module->getPackageName(),
                '--stub' => 'module'
            ]),
            $this->call('module:make:provider', [
                'name' => 'RouteServiceProvider',
                'module' => $module->getPackageName(),
                '--stub' => 'route'
            ])
        ) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param int ...$statuses
     * @return bool
     */
    protected function isFailure(...$statuses): bool
    {
        return in_array(self::FAILURE, $statuses, true);
    }

    protected function validateModuleName(string $name): bool
    {
        return preg_match('/^[a-zA-Z]+$/', $name);
    }

    protected function getEntityName(): string
    {
        if ($this->entityName !== null) {
            return $this->entityName;
        }

        return $this->entityName = Str::studly($this->getOptionOrAsk(
            optionName: 'entity',
            question: 'Enter the entity name (used to create module components)',
            default: $this->moduleName
        ));
    }
}
