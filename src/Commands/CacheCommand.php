<?php

namespace Laraneat\Modules\Commands;


class CacheCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache';

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

        $this->modules->getCached();
        $this->components->info("Modules cached successfully!");

        return self::SUCCESS;
    }
}
