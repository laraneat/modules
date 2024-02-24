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
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Generator\Stub;

/**
 * @group generator
 */
class PolicyMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:policy
                            {name : The name of the policy}
                            {module? : The name or package name of the app module}
                            {--model= : The class name of the model to be used in the policy}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new policy for the specified module.';

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
    protected ModuleComponentType $componentType = ModuleComponentType::Policy;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the policy class name',
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

    protected function getContents(): string
    {
        $stub = 'plain';
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace($this->module, $this->nameArgument, $this->componentType),
            'class' => class_basename($this->nameArgument),
        ];

        $modelOption = $this->getOptionOrAsk(
            'model',
            question: 'Enter the class name of the model to be used in the policy (optional)',
            required: false
        );

        if ($modelOption) {
            $modelClass = $this->getFullClass(
                $modelOption,
                GeneratorHelper::component(ModuleComponentType::Model)
                    ->getFullNamespace($this->module)
            );

            $stub = 'full';
            $stubReplaces['model'] = class_basename($modelClass);
            $stubReplaces['modelNamespace'] = $this->getNamespaceOfClass($modelClass);
            $stubReplaces['modelKebabCase'] = Str::kebab($stubReplaces['model']);
            $stubReplaces['modelsKebabCase'] = Str::kebab(Str::plural($stubReplaces['model']));
            $stubReplaces['modelCamelCase'] = Str::camel($stubReplaces['model']);
            if ($stubReplaces['modelCamelCase'] === 'user') {
                $stubReplaces['modelCamelCase'] = 'model';
            }

            $fullUserClass = $this->getUserModelClass();
            $stubReplaces['user'] = class_basename($fullUserClass);
            $stubReplaces['userNamespace'] = $this->getNamespaceOfClass($fullUserClass);
        }

        return Stub::create("policy/$stub.stub", $stubReplaces)->render();
    }
}
