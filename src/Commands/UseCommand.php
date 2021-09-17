<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laraneat\Modules\Facades\Modules;
use Symfony\Component\Console\Input\InputArgument;

class UseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:use';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use the specified module.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = Str::studly($this->argument('module'));

        if (!Modules::has($module)) {
            $this->error("Module [{$module}] does not exists.");

            return E_ERROR;
        }

        Modules::setUsed($module);

        $this->info("Module [{$module}] used successfully.");

        return 0;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module will be used.'],
        ];
    }
}
