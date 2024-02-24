<?php

namespace Laraneat\Modules\Commands;

class CacheCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache
                            {--vendor : Cache vendor modules}
                            {--app : Cache app modules}';

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
        $buildVendorModules = $this->option('vendor');
        $buildAppModules = $this->option('app');

        if (! $buildAppModules && ! $buildVendorModules) {
            $buildAppModules = $buildVendorModules = true;
        }

        if ($buildVendorModules) {
            $this->modulesRepository->buildVendorModulesManifest();
            $this->components->info("Vendor modules cached!");
        }
        if ($buildAppModules) {
            $this->modulesRepository->buildAppModulesManifest();
            $this->components->info("App modules cached!");
        }

        return self::SUCCESS;
    }
}
