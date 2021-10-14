<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;

class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setting up modules folders for first use.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->generateModulesFolder();
    }

    /**
     * Generate the modules folder.
     */
    public function generateModulesFolder()
    {
        return $this->generateDirectory(
            Modules::config('paths.generators'),
            'Modules directory created successfully',
            'Modules directory already exist'
        );
    }

    /**
     * Generate the specified directory by given $dir.
     *
     * @param $dir
     * @param $success
     * @param $error
     * @return int
     */
    protected function generateDirectory($dir, $success, $error): int
    {
        if (!$this->laravel['files']->isDirectory($dir)) {
            $this->laravel['files']->makeDirectory($dir, 0755, true, true);

            $this->info($success);

            return self::SUCCESS;
        }

        $this->error($error);

        return self::FAILURE;
    }
}
