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
    protected $description = 'Caches modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->modulesRepository->buildModulesManifest();
        $this->components->info("Modules manifest cached!");

        return self::SUCCESS;
    }
}
