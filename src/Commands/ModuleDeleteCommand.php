<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;
use Symfony\Component\Console\Input\InputArgument;

class ModuleDeleteCommand extends Command
{
    protected $name = 'module:delete';
    protected $description = 'Remove a module from the application';

    public function handle(): int
    {
        Modules::delete($this->argument('module'));

        $this->info("Module {$this->argument('module')} has been deleted.");

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module to delete.'],
        ];
    }
}
