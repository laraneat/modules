<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class TestMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:test
                            {name : The name of the test}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--type : The type of test to be created}
                            {--model= : The class name of the model to be used in the test}
                            {--route= : The route name for HTTP tests}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new test for the specified module.';

    /**
     * Module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * The 'name' argument
     */
    protected string $nameArgument;

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType;

    /**
     * The test type.
     */
    protected string $type;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the test class name',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->nameArgument = $this->argument('name');
            $this->ensureNameIsNotReserved($this->nameArgument);
            $this->module = $this->getModuleArgumentOrFail();
            $this->type = $this->getOptionOrChoice(
                'type',
                question: 'Enter the type of test to be created',
                choices: ['unit', 'feature', 'api', 'web', 'cli'],
                default: 'unit'
            );
            $this->componentType = match($this->type) {
                'unit' => ModuleComponentType::UnitTest,
                'feature' => ModuleComponentType::FeatureTest,
                'api' => ModuleComponentType::ApiTest,
                'web' => ModuleComponentType::WebTest,
                'cli' => ModuleComponentType::CliTest,
                default => ModuleComponentType::UnitTest,
            };
        } catch (NameIsReserved|ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return $this->generate(
            $this->getComponentPath($this->module, $this->nameArgument, $this->componentType),
            $this->getContents(),
            $this->option('force')
        );
    }

    protected function getContents(): string
    {
        $stubChoices = match($this->type) {
            'unit' => ['plain'],
            'feature' => ['plain'],
            'api' => ['plain', 'create', 'delete', 'list', 'update', 'view'],
            'web' => ['plain', 'create', 'delete', 'update'],
            'cli' => ['plain'],
            default => ['plain'],
        };

        $stub = count($stubChoices) === 1
            ? $stubChoices[0]
            : $this->getOptionOrChoice(
                'stub',
                'Select the stub you want to use for generator',
                $stubChoices,
                'plain'
            );

        $stubReplaces = [
            'modulePackageName' => $this->module->getPackageName(),
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => class_basename($this->nameArgument),
        ];

        if ($stub !== 'plain') {
            $modelClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'model',
                question: 'Enter the class name of the model to be used in the test',
                componentType: ModuleComponentType::Model,
                module: $this->module
            );
            $stubReplaces['model'] = class_basename($modelClass);
            $stubReplaces['modelNamespace'] = $this->getNamespaceOfClass($modelClass);
            $stubReplaces['models'] = Str::plural($stubReplaces['model']);
            $stubReplaces['modelCamelCase'] = Str::camel($stubReplaces['model']);
            $stubReplaces['modelSnakeCase'] = Str::snake($stubReplaces['model']);
            $stubReplaces['modelsSnakeCase'] = Str::snake($stubReplaces['models']);
            $stubReplaces['modelKebabCase'] = Str::kebab($stubReplaces['model']);
            $stubReplaces['modelsKebabCase'] = Str::plural($stubReplaces['modelKebabCase']);
        }

        if (in_array($this->type, ['api', 'web'])) {
            $stubReplaces['routeName'] = $this->getOptionOrAsk(
                'route',
                'Enter the route name for HTTP tests'
            );
        }

        return Stub::create("test/{$this->type}/{$stub}.stub", $stubReplaces)->render();
    }
}
