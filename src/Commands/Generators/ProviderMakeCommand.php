<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\NameIsReserved;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Generator\Stub;

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
    protected ModuleComponentType $componentType = ModuleComponentType::Provider;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the provider class name',
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
        } catch (NameIsReserved|ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        return $this->generateProvider();
    }

    protected function generateProvider(): int
    {
        $stub = $this->getOptionOrChoice(
            'stub',
            'Select the stub you want to use for generator',
            ['plain', 'module', 'event', 'route'],
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

        $providerDir = GeneratorHelper::component(ModuleComponentType::Provider)->getFullPath($this->module);

        if ($stub === 'module') {
            $stubReplaces = array_merge($stubReplaces, [
                'modulePackageName' => $this->module->getPackageName(),
                'moduleNameKebabCase' => $this->module->getKebabName(),
                'commandsPath' => GeneratorHelper::makeRelativePath(
                    $providerDir,
                    GeneratorHelper::component(ModuleComponentType::CliCommand)->getFullPath($this->module)
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
        } elseif ($stub === 'route') {
            $stubReplaces = array_merge($stubReplaces, [
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

        $result = $this->generate(
            $this->getComponentPath($this->module, $this->nameArgument, $this->componentType),
            Stub::create("provider/{$stub}.stub", $stubReplaces)->render(),
            $this->option('force')
        );

        if ($result !== self::SUCCESS) {
            return $result;
        }

        $this->module->addProvider($stubReplaces['namespace'] . '\\' . $stubReplaces['class']);

        return self::SUCCESS;
    }
}
