<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\TestMakeCommand as BaseTestMakeCommand;
use Illuminate\Support\Str;
use Override;

/**
 * Tests of a module live in "modules/blog/tests" with the "Modules\Blog\Tests" namespace.
 *
 * @internal
 */
final class TestMakeCommand extends BaseTestMakeCommand
{
    use ResolvesModule;

    #[Override]
    protected function rootNamespace()
    {
        $module = $this->currentModule();

        return $module === null ? parent::rootNamespace() : $module->namespace.'\\Tests';
    }

    #[Override]
    protected function getPath($name)
    {
        $module = $this->currentModule();

        if ($module === null) {
            return parent::getPath($name);
        }

        return $module->path.'/tests'.str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name)).'.php';
    }
}
