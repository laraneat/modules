<?php

namespace Laraneat\Modules\Generators;

use Illuminate\Console\Command as Console;
use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class ModuleComponentsGenerator extends Generator
{
    /**
     * The module instance.
     */
    protected Module $module;

    /**
     * Entity name.
     */
    protected string $entityName;

    /**
     * The laravel console instance.
     */
    protected ?Console $console;

    /**
     * The module components type.
     */
    protected string $type = 'api';

    public function __construct(
        Module $module,
        ?string $entityName = null,
        ?string $type = null,
        ?Console $console = null,
    ) {
        $this->module = $module;
        $this->setEntityName($entityName ?: $module->getStudlyName());
        $this->setType($type ?: 'api');
        $this->console = $console;
    }

    /**
     * Get the entity name.
     */
    public function getEntityName(): string
    {
        return $this->entityName ?: $this->module->getStudlyName();
    }

    /**
     * Set entity name.
     */
    public function setEntityName(string $entityName): static
    {
        $this->entityName = Str::studly($entityName);

        return $this;
    }

    /**
     * Get the module components type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the module components type.
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the laravel console instance.
     *
     * @return Console|null
     */
    public function getConsole(): ?Console
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     *
     * @param Console $console
     *
     * @return $this
     */
    public function setConsole(Console $console)
    {
        $this->console = $console;

        return $this;
    }

    public function generate(): int
    {
        $name = $this->module->getStudlyName();
        $type = $this->getType();

        $this->generateBaseComponentsForModule($this->module);

        if ($this->type === 'api') {
            $this->generateApiComponentsForModule($this->module);
        }

        $this->console->info("[$type] components for [$name] module created successfully.");

        return $this->console::SUCCESS;
    }

    public function generateBaseComponentsForModule(Module $module): void
    {
        $modulePackageName = $module->getStudlyName();
        $entityName = $this->getEntityName();
        $snakeEntityName = Str::snake($entityName);
        $snakePluralEntityName = Str::plural($snakeEntityName);

        if (GeneratorHelper::component('factory')->generate() === true) {
            $this->console->call('module:make:factory', [
                'name' => "{$entityName}Factory",
                'module' => $modulePackageName,
                '--model' => $entityName,
            ]);
        }

        if (GeneratorHelper::component('migration')->generate() === true) {
            $this->console->call('module:make:migration', [
                'name' => "create_{$snakePluralEntityName}_table",
                'module' => $modulePackageName,
                '--stub' => 'create',
            ]);
        }

        if (GeneratorHelper::component('seeder')->generate() === true) {
            $this->console->call('module:make:seeder', [
                'name' => "{$entityName}PermissionsSeeder_1",
                'module' => $modulePackageName,
                '--stub' => 'permissions',
                '--model' => $entityName,
            ]);
        }

        if (GeneratorHelper::component('dto')->generate() === true) {
            $this->console->call('module:make:dto', [
                'name' => "Create{$entityName}DTO",
                'module' => $modulePackageName,
            ]);
            $this->console->call('module:make:dto', [
                'name' => "Update{$entityName}DTO",
                'module' => $modulePackageName,
            ]);
        }

        if (GeneratorHelper::component('model')->generate() === true) {
            $this->console->call('module:make:model', [
                'name' => $entityName,
                'module' => $modulePackageName,
                '--stub' => 'full',
                '--factory' => "{$entityName}Factory",
            ]);
        }

        if (GeneratorHelper::component('policy')->generate() === true) {
            $this->console->call('module:make:policy', [
                'name' => "{$entityName}Policy",
                'module' => $modulePackageName,
                '--stub' => 'full',
                '--model' => $entityName,
            ]);
        }
    }

    public function generateApiComponentsForModule(Module $module): void
    {
        $actionVerbs = ['create', 'update', 'delete', 'list', 'view'];

        $modulePackageName = $module->getStudlyName();
        $entityName = $this->getEntityName();
        $pluralEntityName = Str::plural($entityName);
        $camelEntityName = Str::camel($entityName);
        $dashedPluralEntityName = Str::snake($pluralEntityName, '-');
        $underlinedPluralEntityName = Str::snake($pluralEntityName, '_');

        if (GeneratorHelper::component('api-query-wizard')->generate() === true) {
            $this->console->call('module:make:wizard', [
                'name' => "{$pluralEntityName}QueryWizard",
                'module' => $modulePackageName,
                '--stub' => 'eloquent',
            ]);
            $this->console->call('module:make:wizard', [
                'name' => "{$entityName}QueryWizard",
                'module' => $modulePackageName,
                '--stub' => 'model',
            ]);
        }

        if (GeneratorHelper::component('api-resource')->generate() === true) {
            $this->console->call('module:make:resource', [
                'name' => "{$entityName}Resource",
                'module' => $modulePackageName,
                '--stub' => 'single',
            ]);
        }

        foreach ($actionVerbs as $actionVerb) {
            $studlyActionVerb = Str::studly($actionVerb);

            $resourceClass = "{$entityName}Resource";
            $dtoClass = "{$studlyActionVerb}{$entityName}DTO";
            $routeName = 'api.' . $underlinedPluralEntityName . '.' . $actionVerb;

            if ($actionVerb === "list") {
                $actionClass = "{$studlyActionVerb}{$pluralEntityName}Action";
                $requestClass = "{$studlyActionVerb}{$pluralEntityName}Request";
                $wizardClass = "{$pluralEntityName}QueryWizard";
            } else {
                $actionClass = "{$studlyActionVerb}{$entityName}Action";
                $requestClass = "{$studlyActionVerb}{$entityName}Request";
                $wizardClass = "{$entityName}QueryWizard";
            }

            if (GeneratorHelper::component('action')->generate() === true) {
                $this->console->call('module:make:action', [
                    'name' => $actionClass,
                    'module' => $modulePackageName,
                    '--stub' => $actionVerb,
                    '--dto' => $dtoClass,
                    '--model' => $entityName,
                    '--request' => $requestClass,
                    '--resource' => $resourceClass,
                    '--wizard' => $wizardClass,
                ]);
            }

            if (GeneratorHelper::component("api-controller")->generate() === true) {
                $this->console->call('module:make:controller', [
                    'name' => 'Controller',
                    'module' => $modulePackageName,
                    '--ui' => 'api',
                ]);
            }

            if (GeneratorHelper::component("api-request")->generate() === true) {
                $this->console->call('module:make:request', [
                    'name' => $requestClass,
                    'module' => $modulePackageName,
                    '--ui' => 'api',
                    '--dto' => $dtoClass,
                    '--stub' => $actionVerb,
                    '--model' => $entityName,
                ]);
            }

            if (GeneratorHelper::component("api-route")->generate() === true) {
                $actionMethodsMap = [
                    'create' => 'post',
                    'update' => 'patch',
                    'delete' => 'delete',
                    'list' => 'get',
                    'view' => 'get',
                ];

                $url = $dashedPluralEntityName;
                if (in_array($actionVerb, ['update', 'delete', 'view'])) {
                    $url .= '/{' . $camelEntityName . '}';
                }

                $filePath = Str::snake(str_replace('Action', '', $actionClass), '_');
                $filePath = 'v1/' . $filePath;

                $this->console->call('module:make:route', [
                    'name' => $filePath,
                    'module' => $modulePackageName,
                    '--ui' => 'api',
                    '--action' => $actionClass,
                    '--method' => $actionMethodsMap[$actionVerb],
                    '--url' => $url,
                    '--name' => $routeName,
                ]);
            }

            if (GeneratorHelper::component("api-test")->generate() === true) {
                $testClass = $actionVerb === 'list'
                    ? "{$studlyActionVerb}{$pluralEntityName}Test"
                    : "{$studlyActionVerb}{$entityName}Test";

                $this->console->call('module:make:test', [
                    'name' => $testClass,
                    'module' => $modulePackageName,
                    '--type' => 'api',
                    '--stub' => $actionVerb,
                    '--model' => $entityName,
                    '--route' => $routeName,
                ]);
            }
        }
    }
}
