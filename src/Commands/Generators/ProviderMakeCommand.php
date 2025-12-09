<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Generator\Stub;
use Laraneat\Modules\Support\ModuleConfigWriter;

/**
 * @group generator
 */
class ProviderMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:provider
                            {name : The name of the provider class}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new provider for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Provider;

    /**
     * Selected stub name (cached for afterGenerate).
     */
    protected string $selectedStub;

    /**
     * Stub replacements (cached for afterGenerate).
     */
    protected array $stubReplaces;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the provider class name',
        ];
    }

    protected function getContents(): string
    {
        $this->selectedStub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'module', 'event', 'route'],
            'plain'
        );

        $this->stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => class_basename($this->nameArgument),
        ];

        $providerDir = GeneratorHelper::component(ModuleComponentType::Provider)->getFullPath($this->module);

        if ($this->selectedStub === 'module') {
            $this->stubReplaces = array_merge($this->stubReplaces, [
                'modulePackageName' => $this->module->getPackageName(),
                'moduleNameKebabCase' => $this->module->getKebabName(),
                'commandsNamespace' => str_replace('\\', '\\\\', GeneratorHelper::component(ModuleComponentType::CliCommand)->getFullNamespace($this->module)),
                'commandsPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::CliCommand)->getFullPath($this->module)
                ),
                'configPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::Config)->getFullPath($this->module)
                ),
                'langPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::Lang)->getFullPath($this->module)
                ),
                'viewsPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::View)->getFullPath($this->module)
                ),
                'migrationsPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::Migration)->getFullPath($this->module)
                ),
            ]);
        } elseif ($this->selectedStub === 'route') {
            $this->stubReplaces = array_merge($this->stubReplaces, [
                'modulePackageName' => $this->module->getPackageName(),
                'webControllerNamespace' => str_replace('\\', '\\\\', GeneratorHelper::component(ModuleComponentType::WebController)->getFullNamespace($this->module)),
                'apiControllerNamespace' => str_replace('\\', '\\\\', GeneratorHelper::component(ModuleComponentType::ApiController)->getFullNamespace($this->module)),
                'webRoutesPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::WebRoute)->getFullPath($this->module)
                ),
                'apiRoutesPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::ApiRoute)->getFullPath($this->module)
                ),
            ]);
        }

        return Stub::create("provider/{$this->selectedStub}.stub", $this->stubReplaces)->render();
    }

    protected function afterGenerate(): void
    {
        $this->laravel->make(ModuleConfigWriter::class)
            ->addProvider($this->module, $this->stubReplaces['namespace'] . '\\' . $this->stubReplaces['class']);
    }
}
