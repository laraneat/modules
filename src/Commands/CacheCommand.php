<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;

class CacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a modules cache.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('module:clear');

        Modules::getCached();
        $this->info("Modules cached successfully!");

        return 0;
    }
}
