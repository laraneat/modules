<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Exceptions\ModulesException;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerJson;
use Laraneat\Modules\Scaffold\ComposerRunner;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:sync', description: 'Add every module to the composer.json of the application and install the outdated ones')]
final class SyncCommand extends Command
{
    protected $signature = 'module:sync
        {--no-update : Do not run "composer update" for the outdated modules}';

    public function handle(ModuleRepository $modules, ApplicationComposer $composer, ComposerRunner $runner): int
    {
        try {
            $modules->refresh();

            $root = $composer->composerJson();
            $installed = $composer->installed($root);
            $outdated = [];

            foreach ($modules->all() as $module) {
                $changed = $composer->addModule($root, $module, $modules->modulesPath());

                if ($changed || ! $installed->isCurrent($module->package, ComposerJson::read($module->path.'/composer.json')->toArray())) {
                    $outdated[] = $module->package;
                }
            }

            $root->save();
        } catch (ModulesException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($outdated === []) {
            $this->components->info('Modules are in sync with Composer.');

            return self::SUCCESS;
        }

        if ($this->option('no-update')) {
            $this->components->warn('Install the outdated modules with: '.$runner->commandLine('update', $outdated));

            return self::SUCCESS;
        }

        if (! $runner->update($outdated, $this->streamOutput(...))) {
            $this->components->error('Composer failed to install the modules. Fix the problem and run: '.$runner->commandLine('update', $outdated));

            return self::FAILURE;
        }

        $this->components->info('Modules are in sync with Composer.');

        return self::SUCCESS;
    }

    private function streamOutput(string $type, string $line): void
    {
        $this->output->write($line);
    }
}
