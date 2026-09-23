<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\ViewMakeCommand as BaseViewMakeCommand;
use Illuminate\Support\Str;
use Override;

/**
 * The test of a module view lives in the module tests and renders "blog::posts.show".
 *
 * @internal
 */
final class ViewMakeCommand extends BaseViewMakeCommand
{
    use ResolvesModule;

    #[Override]
    protected function getTestPath()
    {
        $module = $this->currentModule();

        if ($module === null) {
            return parent::getTestPath();
        }

        $class = Str::after($this->testClassFullyQualifiedName(), $module->namespace.'\\Tests\\');

        return $module->path.'/tests/'.str_replace('\\', '/', $class).'Test.php';
    }

    #[Override]
    protected function testClassFullyQualifiedName()
    {
        $module = $this->currentModule();

        return ($module === null ? '' : $module->namespace.'\\').parent::testClassFullyQualifiedName();
    }

    #[Override]
    protected function testViewName()
    {
        $module = $this->currentModule();

        return ($module === null ? '' : $module->name.'::').parent::testViewName();
    }
}
