<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Exceptions\ModulesException;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerRunner;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:delete', description: 'Delete a module: uninstall it with Composer, then remove its directory')]
final class DeleteCommand extends Command
{
    protected $signature = 'module:delete
        {name : The module name}
        {--force : Do not ask for confirmation}
        {--no-update : Only edit composer.json, do not run "composer remove"}';

    public function handle(
        ModuleRepository $modules,
        ApplicationComposer $composer,
        ComposerRunner $runner,
        Filesystem $files,
    ): int {
        try {
            $name = $this->input->getArgument('name');
            $module = $modules->get(is_string($name) ? $name : '');

            if (is_link($module->path)) {
                $this->components->error("[{$composer->relative($module->path)}] is a symbolic link. Remove the module by hand.");

                return self::FAILURE;
            }

            if (! $this->confirmed($module->name)) {
                return self::FAILURE;
            }

            $root = $composer->composerJson();
            $original = $files->get($composer->composerJsonPath());
            $root->removeAutoloadDev(array_keys($composer->autoloadDev($module)));

            if ($this->option('no-update')) {
                $root->removeRequire($module->package);
            }

            $vendorLink = $composer->installed($root)->path($module->package);
            $root->save();
        } catch (ModulesException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        // Uninstall first: when Composer fails, the module and composer.json are left as they were.
        if (! $this->option('no-update') && ! $runner->remove([$module->package], $this->streamOutput(...))) {
            $files->put($composer->composerJsonPath(), $original);
            $this->components->error('Composer failed to remove the module, nothing was deleted.');

            return self::FAILURE;
        }

        // deleteDirectory() reports success even when some files could not be deleted.
        $files->deleteDirectory($module->path);
        $modules->refresh();

        if (file_exists($module->path)) {
            $this->components->error("[{$composer->relative($module->path)}] could not be deleted completely. Delete it by hand.");

            return self::FAILURE;
        }

        // Composer does not remove the link of a path package whose directory is gone.
        // On Windows, file_exists() is true for a link to a deleted directory, and such a link, like the
        // junction Composer creates there, is removed with rmdir().
        if (is_link($vendorLink) && ! is_dir($vendorLink) && ! $files->delete($vendorLink)) {
            @rmdir($vendorLink);
        }

        $this->components->info("Module [{$module->name}] deleted.");

        if ($this->option('no-update')) {
            $this->components->warn('Uninstall it with: '.$runner->commandLine('update', [$module->package]));
        }

        return self::SUCCESS;
    }

    private function confirmed(string $name): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Use --force to delete a module in non-interactive mode.');

            return false;
        }

        if ($this->components->confirm("Delete the [{$name}] module and all of its files?")) {
            return true;
        }

        $this->components->warn('The module was not deleted.');

        return false;
    }

    private function streamOutput(string $type, string $line): void
    {
        $this->output->write($line);
    }
}
