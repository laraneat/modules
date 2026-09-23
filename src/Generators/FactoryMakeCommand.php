<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseFactoryMakeCommand;
use Override;

/**
 * Factories of a module live in "Modules\Blog\Database\Factories" instead of "Database\Factories".
 *
 * @internal
 */
final class FactoryMakeCommand extends BaseFactoryMakeCommand
{
    use ResolvesModule;

    #[Override]
    protected function buildClass($name)
    {
        $class = parent::buildClass($name);
        $module = $this->currentModule();

        if ($module === null) {
            return $class;
        }

        return (string) preg_replace_callback(
            '/^namespace Database\\\\Factories(?=[\\\;])/m',
            static fn (): string => 'namespace '.$module->namespace.'\\Database\\Factories',
            $class,
            1,
        );
    }
}
