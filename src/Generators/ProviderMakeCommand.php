<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand as BaseProviderMakeCommand;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerJson;
use Override;

/**
 * A module provider is registered in "extra.laravel.providers" of the module composer.json,
 * not in bootstrap/providers.php of the application.
 *
 * @internal
 */
final class ProviderMakeCommand extends BaseProviderMakeCommand
{
    use ResolvesModule;

    #[Override]
    public function handle()
    {
        $module = $this->currentModule();

        if ($module === null) {
            return parent::handle();
        }

        if (GeneratorCommand::handle() === false) {
            return false;
        }

        $provider = $this->qualifyClass($this->getNameInput());

        ComposerJson::read($module->path.'/composer.json')->addProvider($provider)->save();

        $composerJson = $this->laravel->make(ApplicationComposer::class)->relative($module->path.'/composer.json');

        $this->components->info("Provider [{$provider}] registered in [{$composerJson}]. Run \"php artisan module:sync\" to install it.");

        return null;
    }
}
