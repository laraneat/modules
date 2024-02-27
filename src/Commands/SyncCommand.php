<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;

class SyncCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh and sync modules with composer.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->modulesRepository->pruneModulesManifest();

        try {
            $this->modulesRepository->syncWithComposer($this->output);
            $this->components->info("Modules completed successfully!");
        } catch (ModuleHasNoNamespace|ModuleHasNonUniquePackageName $e) {
            $this->components->error($e->getMessage());
        } catch (ComposerException $e) {
            $this->components->error($e->getMessage());
            $modulePackageNames = join(" ", array_keys($this->modulesRepository->getModules()));
            $this->components->info("Please run <kbd>composer update {$modulePackageNames}</kbd> manually");
        }

        return self::SUCCESS;
    }
}
