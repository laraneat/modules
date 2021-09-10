<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Module;
use Laraneat\Modules\Publishing\AssetPublisher;
use Symfony\Component\Console\Input\InputArgument;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a module\'s assets to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($name = $this->argument('module')) {
            $this->publish($name);

            return 0;
        }

        $this->publishAll();

        return 0;
    }

    /**
     * Publish assets from all modules.
     */
    public function publishAll(): void
    {
        foreach ($this->laravel['modules']->allEnabled() as $module) {
            $this->publish($module);
        }
    }

    /**
     * Publish assets from the specified module.
     *
     * @param string $moduleName
     */
    public function publish(string $moduleName): void
    {
        if ($moduleName instanceof Module) {
            $module = $moduleName;
        } else {
            $module = $this->laravel['modules']->findOrFail($moduleName);
        }

        with(new AssetPublisher($module))
            ->setRepository($this->laravel['modules'])
            ->setConsole($this)
            ->publish();

        $this->line("<info>Published</info>: {$module->getStudlyName()}");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }
}