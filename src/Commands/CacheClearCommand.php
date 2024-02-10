<?php

namespace Laraneat\Modules\Commands;

class CacheClearCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:clear';

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
        $this->modules->flushCache();
        $this->components->info("Modules cache cleared!");

        return self::SUCCESS;
    }
}
