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
use Illuminate\Routing\Console\ControllerMakeCommand as BaseControllerMakeCommand;
use Illuminate\Support\Facades\Config;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModuleRepository;
use Stringable;
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
        BaseControllerMakeCommand::class => ControllerMakeCommand::class,
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
            $context = $this->getLaravel()->make(ModuleContext::class);

            // A generator called by another one (make:model Post -a) runs in the module of the caller.
            if (! is_string($name)) {
                if (($module = $context->module()) !== null) {
                    ModuleOption::qualify($this, $input, $module);
                }

                return $this->execute($input, $output);
            }

            $module = $this->getLaravel()->make(ModuleRepository::class)->find($name);

            if ($module === null) {
                $this->components->error("Module [{$name}] not found.");

                return Command::FAILURE;
            }

            return $context->run($module, function () use ($input, $output, $module): int {
                ModuleOption::qualify($this, $input, $module);

                return $this->execute($input, $output);
            });
        }, $command, $command::class));
    }

    /**
     * Apply the "generators" config: "make:controller Post" becomes "Modules\\Blog\\<namespace>\\Post".
     * The namespace is relative to the root namespace of the generator in the module, which is
     * "Modules\\Blog\\Tests" for make:test and "Modules\\Blog\\Database\\Seeders" for make:seeder.
     * The "make:model" namespace also applies to the --model and --parent options.
     * A name in the module namespace is left as it is.
     */
    public static function qualify(Command $command, InputInterface $input, Module $module): void
    {
        if (! $command instanceof GeneratorCommand) {
            return;
        }

        $generators = Config::array('modules.generators', []);
        $namespace = $generators[$command->getName()] ?? null;

        if (is_string($namespace) && $input->hasArgument('name')) {
            $root = (fn (): string => $this->rootNamespace())->call($command);

            // The --test option of generators passes a Stringable.
            self::qualifyInput($input->getArgument('name'), $module, $root, $namespace, static fn (string $class) => $input->setArgument('name', $class));
        }

        if (is_string($models = $generators['make:model'] ?? null)) {
            foreach (['model', 'parent'] as $option) {
                if ($input->hasOption($option)) {
                    self::qualifyInput($input->getOption($option), $module, $module->namespace, $models, static fn (string $class) => $input->setOption($option, $class));
                }
            }
        }
    }

    /**
     * @param  Closure(string): void  $set
     */
    private static function qualifyInput(mixed $class, Module $module, string $root, string $namespace, Closure $set): void
    {
        if (! is_string($class) && ! $class instanceof Stringable) {
            return;
        }

        $class = trim(str_replace('/', '\\', (string) $class), '\\');

        if ($class !== '' && ! str_starts_with($class, $module->namespace.'\\')) {
            $set(rtrim($root, '\\').'\\'.trim($namespace, '\\').'\\'.$class);
        }
    }
}
