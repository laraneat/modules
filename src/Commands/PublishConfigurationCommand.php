<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laraneat\Modules\Facades\Modules;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PublishConfigurationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:publish-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a module\'s config files to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($module = $this->argument('module')) {
            $this->publishConfiguration($module);

            return 0;
        }

        foreach (Modules::allEnabled() as $module) {
            $this->publishConfiguration($module->getName());
        }

        return 0;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    private function getServiceProviderForModule(string $moduleName): string
    {
        $namespace = $this->laravel['config']->get('modules.namespace');
        $studlyName = Str::studly($moduleName);

        return "$namespace\\$studlyName\\Providers\\{$studlyName}ServiceProvider";
    }

    /**
     * @param string $moduleName
     */
    private function publishConfiguration(string $moduleName): void
    {
        $this->call('vendor:publish', [
            '--provider' => $this->getServiceProviderForModule($moduleName),
            '--force' => $this->option('force'),
            '--tag' => ['config'],
        ]);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module being used.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['--force', '-f', InputOption::VALUE_NONE, 'Force the publishing of config files'],
        ];
    }
}
