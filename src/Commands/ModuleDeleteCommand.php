<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Exceptions\ModuleNotFoundException;

class ModuleDeleteCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:delete
                            {module?* : Module name(s) to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the specified module(s)';

    /**
     * @throws ModuleNotFoundException
     */
    public function handle(): int
    {
        $this->components->info('Deleting module ...');

        /** @var array<string> $moduleNamesToDelete */
        $moduleNamesToDelete = $this->argument('module');

        foreach($moduleNamesToDelete as $moduleName) {
            $this->modules->delete($moduleName);
            $this->components->info("Module $moduleName has been deleted.");
        }

        return self::SUCCESS;
    }
}
