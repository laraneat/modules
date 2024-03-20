<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\ConfirmableTrait;
use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;

class ModuleDeleteCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:delete
                            {module?* : Module name(s) or package name(s) to delete}
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
        } catch (ModuleNotFound|ModuleHasNonUniquePackageName|ModuleHasNoNamespace $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach($modulesToDelete as $moduleToDelete) {
            try {
                $status = $moduleToDelete->delete($this->output);
                if ($status) {
                    $this->components->info("Module [{$moduleToDelete->getPackageName()}] has been deleted.");
                } else {
                    $this->components->error("Failed to remove module [{$moduleToDelete->getPackageName()}].");
                }
            } catch (ComposerException $exception) {
                $this->components->error($exception->getMessage());
                $this->components->info("Please run <info>composer remove {$moduleToDelete->getPackageName()}</info> manually");
            }
        }

        return self::SUCCESS;
    }
}
