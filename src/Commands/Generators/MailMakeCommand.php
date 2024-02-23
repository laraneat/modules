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
class MailMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:mail
                            {name : The name of the mail class}
                            {module? : The name or package name of the app module}
                            {--subject= : The subject of mail}
                            {--view= : The view for the mail}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new mail class for the specified module.';

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
    protected ModuleComponentType $componentType = ModuleComponentType::Mail;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the mail class name',
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
        $classBaseName = class_basename($this->nameArgument);
        $subject = $this->getOptionOrAsk(
            "subject",
            "Enter the subject of mail",
            Str::headline($classBaseName)
        );
        $view = $this->getOptionOrAsk(
            "view",
            "Enter the view for the mail",
            'view.name'
        );

        $stubReplaces = [
            'namespace' => $this->getComponentNamespace(
                $this->module,
                $this->nameArgument,
                $this->componentType
            ),
            'class' => $classBaseName,
            'subject' => $subject,
            'view' => $view
        ];

        return Stub::create("mail.stub", $stubReplaces)->render();
    }
}
