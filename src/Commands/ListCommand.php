<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class ListCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list
                            {--vendor : Outputs vendor modules}
                            {--app : Outputs app modules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $listVendorModules = $this->option('vendor');
        $listAppModules = $this->option('app');

        if (!$listAppModules && !$listVendorModules || $listAppModules && $listVendorModules) {
            $modules = $this->modulesRepository->getModules();
        } else if ($listVendorModules) {
            $modules = $this->modulesRepository->getVendorModules();
        } else {
            $modules = $this->modulesRepository->getAppModules();
        }

        $this->table(
            ['Package Name', 'Namespace', 'Path'],
            collect($modules)
                ->map(fn (Module $module) => [$module->getPackageName(), $module->getNamespace(), $module->getPath()])
                ->values()
                ->toArray()
        );

        return self::SUCCESS;
    }
}
