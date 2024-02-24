<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Enums\ModuleType;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class RequestMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:request
                            {name : The name of the request}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--ui= : The UI for which the request will be created}
                            {--dto= : The class name of the DTO to be used in the request}
                            {--model= : The class name of the model to be used in the request}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new request for the specified module.';

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
     * The UI for which the request will be created.
     * ('web' or 'api')
     */
    protected string $ui;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the request class name',
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
            $this->module = $this->getModuleArgumentOrFail(ModuleType::App);
            $this->ui = $this->getOptionOrChoice(
                'ui',
                question: 'Enter the UI for which the request will be created',
                choices: ['api', 'web'],
                default: 'api'
            );
            $this->componentType = $this->ui === 'api'
                ? ModuleComponentType::ApiRequest
                : ModuleComponentType::WebRequest;
        } catch (ModuleNotFound|NameIsReserved|ModuleHasNonUniquePackageName $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return $this->generate(
            $this->getComponentPath($this->module, $this->nameArgument, $this->componentType),
            $this->getContents(),
            $this->option('force')
        );
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->nameArgument, $this->componentType);
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOrChoice(
            optionName: 'stub',
            question: 'Select the stub you want to use for generator',
            choices: ($this->ui === "web")
                ? ['plain', 'create', 'delete', 'update']
                : ['plain', 'create', 'delete', 'list', 'update', 'view'],
            default: 'plain'
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        if ($stub !== 'plain') {
            if ($stub === 'create' || $stub === 'update') {
                $dtoClass = $this->getFullClassFromOptionOrAsk(
                    optionName: 'dto',
                    question: 'Enter the class name of the DTO to be used in the request',
                    componentType: ModuleComponentType::Dto,
                    module: $this->module
                );
                $stubReplaces['dto'] = class_basename($dtoClass);
                $stubReplaces['dtoNamespace'] = $this->getNamespaceOfClass($dtoClass);
            }

            $modelClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'model',
                question: 'Enter the class name of the model to be used in the request',
                componentType: ModuleComponentType::Model,
                module: $this->module
            );
            $stubReplaces['model'] = class_basename($modelClass);
            $stubReplaces['modelCamelCase'] = Str::camel($stubReplaces['model']);
            $stubReplaces['modelNamespace'] = $this->getNamespaceOfClass($modelClass);
        }

        return Stub::create("request/$stub.stub", $stubReplaces)->render();
    }
}
