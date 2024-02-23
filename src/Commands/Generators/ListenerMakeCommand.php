<?php

namespace Laraneat\Modules\Commands\Generators;

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
class ListenerMakeCommand extends BaseComponentGeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:listener
                            {name : The name of the listener class}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--event= : The class name of the event to listen to}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new listener class for the specified module.';

    /**
     * The module instance
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
    protected ModuleComponentType $componentType = ModuleComponentType::Listener;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the listener class name',
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
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'queued'],
            'plain'
        );
        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        $eventClass = $this->getFullClassFromOptionOrAsk(
            optionName: 'event',
            question: 'Enter the class name of the event to listen to',
            componentType: ModuleComponentType::Event,
            module: $this->module
        );
        $stubReplaces['event'] = class_basename($eventClass);
        $stubReplaces['eventNamespace'] = $this->getNamespaceOfClass($eventClass);

        return Stub::create("listener/$stub.stub", $stubReplaces)->render();
    }
}
