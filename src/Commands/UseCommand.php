<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Support\Str;

class UseCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:use {module : The name of module will be used}';

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

        if (!$this->modules->has($module)) {
            $this->error("Module <info>$module</info> does not exists.");

            return self::FAILURE;
        }

        $this->modules->setUsed($module);

        $this->components->info("Module <info>$module</info> used successfully.");

        return self::SUCCESS;
    }
}
