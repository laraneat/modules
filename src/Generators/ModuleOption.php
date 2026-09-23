<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\MigrationGeneratorCommand;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseFactoryMakeCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseSeederMakeCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ComponentMakeCommand as BaseComponentMakeCommand;
use Illuminate\Foundation\Console\MailMakeCommand as BaseMailMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;
use Illuminate\Foundation\Console\NotificationMakeCommand as BaseNotificationMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand as BaseProviderMakeCommand;
use Illuminate\Foundation\Console\TestMakeCommand as BaseTestMakeCommand;
use Illuminate\Foundation\Console\ViewMakeCommand as BaseViewMakeCommand;
use Illuminate\Support\Facades\Config;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModuleRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds "--module" to every generator: all GeneratorCommand subclasses (the framework ones
 * and those of packages, such as make:action or make:data), make:migration and the
 * migration generators of framework tables (make:queue-table, make:session-table...).
 *
 * @internal
 */
final class ModuleOption
{
    private const array GENERATORS = [
        BaseComponentMakeCommand::class => ComponentMakeCommand::class,
        BaseFactoryMakeCommand::class => FactoryMakeCommand::class,
        BaseMailMakeCommand::class => MailMakeCommand::class,
        BaseModelMakeCommand::class => ModelMakeCommand::class,
        BaseNotificationMakeCommand::class => NotificationMakeCommand::class,
        BaseProviderMakeCommand::class => ProviderMakeCommand::class,
        BaseSeederMakeCommand::class => SeederMakeCommand::class,
        BaseTestMakeCommand::class => TestMakeCommand::class,
        BaseViewMakeCommand::class => ViewMakeCommand::class,
    ];

    public static function register(Container $app): void
    {
        $app->singleton(ModuleContext::class);

        $app->resolving(GeneratorCommand::class, self::addTo(...));
        $app->resolving(MigrateMakeCommand::class, self::addTo(...));
        $app->resolving(MigrationGeneratorCommand::class, self::addTo(...));

        // Generators that hard-code a namespace, a destination or a view name of the application.
        foreach (self::GENERATORS as $base => $generator) {
            $app->extend($base, static fn (Command $command, Container $app): Command => new $generator($app->make(Filesystem::class)));
        }
    }

    /**
     * The option is added when the command is resolved, and the command code is replaced by
     * a closure bound to the command, so its protected execute() still does the work.
     * Symfony binds the input before the code runs, which is why this can not happen earlier.
     */
    public static function addTo(Command $command): void
    {
        if ($command->getDefinition()->hasOption('module')) {
            return;
        }

        $command->addOption('module', null, InputOption::VALUE_REQUIRED, 'Generate the file inside the given module');

        $command->setCode(Closure::bind(function (InputInterface $input, OutputInterface $output): int {
            /** @var Command $this */
            $name = $input->getOption('module');

            if (! is_string($name)) {
                return $this->execute($input, $output);
            }

            $laravel = $this->getLaravel();
            $module = $laravel->make(ModuleRepository::class)->find($name);

            if ($module === null) {
                $this->components->error("Module [{$name}] not found.");

                return Command::FAILURE;
            }

            ModuleOption::qualifyName($this, $input, $module, Config::array('modules.generators', []));

            return $laravel->make(ModuleContext::class)->run($module, fn (): int => $this->execute($input, $output));
        }, $command, $command::class));
    }

    /**
     * Apply the "generators" config: "make:controller Post" becomes "Modules\Blog\<namespace>\Post".
     * A fully qualified name is left as is by the generator.
     *
     * @param  array<array-key, mixed>  $generators
     */
    public static function qualifyName(Command $command, InputInterface $input, Module $module, array $generators): void
    {
        $namespace = $generators[$command->getName()] ?? null;

        if (! is_string($namespace) || ! $input->hasArgument('name') || ! is_string($class = $input->getArgument('name'))) {
            return;
        }

        $class = trim(str_replace('/', '\\', $class), '\\');

        if ($class !== '' && ! str_starts_with($class, $module->namespace.'\\')) {
            $input->setArgument('name', $module->namespace.'\\'.trim($namespace, '\\').'\\'.$class);
        }
    }
}
