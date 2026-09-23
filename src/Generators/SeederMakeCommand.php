<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Database\Console\Seeds\SeederMakeCommand as BaseSeederMakeCommand;
use Override;

/**
 * Seeders of a module live in "Modules\Blog\Database\Seeders" instead of "Database\Seeders".
 *
 * @internal
 */
final class SeederMakeCommand extends BaseSeederMakeCommand
{
    use ResolvesModule;

    #[Override]
    protected function rootNamespace()
    {
        $module = $this->currentModule();

        return $module === null ? parent::rootNamespace() : $module->namespace.'\\Database\\Seeders\\';
    }
}
