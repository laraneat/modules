<?php

namespace Laraneat\Modules\Commands;

class CacheClearCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:clear
                            {--vendor : Clear vendor modules cache}
                            {--app : Clear app modules cache}';

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
        $clearVendorModules = $this->option('vendor');
        $clearAppModules = $this->option('app');
        if (!$clearAppModules && !$clearVendorModules) {
            $clearAppModules = $clearVendorModules = true;
        }

        if ($clearVendorModules) {
            $this->modulesRepository->pruneVendorModulesManifest();
            $this->components->info("Vendor modules cache cleared!");
        }
        if ($clearAppModules) {
            $this->modulesRepository->pruneAppModulesManifest();
            $this->components->info("App modules cache cleared!");
        }

        return self::SUCCESS;
    }
}
