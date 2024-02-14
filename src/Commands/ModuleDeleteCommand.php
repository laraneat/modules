<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;

class ModuleDeleteCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:delete
                            {module?* : Module name(s) to delete}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the specified module(s)';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        try {
            $modulesToDelete = $this->getModuleArgumentOrFail();
        } catch (ModuleNotFoundException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach($modulesToDelete as $moduleToDelete) {
            $moduleToDelete->delete();
            $this->components->info("Module [{$moduleToDelete->getPackageName()}] has been deleted.");
        }

        return self::SUCCESS;
    }
}
