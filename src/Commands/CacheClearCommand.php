<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;

class CacheClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear modules cache.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Modules::flushCache();

        return 0;
    }
}
