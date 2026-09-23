<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\ComponentMakeCommand as BaseComponentMakeCommand;

/**
 * @internal
 */
final class ComponentMakeCommand extends BaseComponentMakeCommand
{
    use NamespacesViews;
}
