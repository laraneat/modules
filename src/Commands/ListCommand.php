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
    protected $signature = 'module:list';

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
        $this->table(
            ['Package Name', 'Namespace', 'Path'],
            collect($this->modulesRepository->getModules())
                ->map(fn (Module $module) => [$module->getPackageName(), $module->getNamespace(), $module->getPath()])
                ->values()
                ->toArray()
        );

        return self::SUCCESS;
    }
}
