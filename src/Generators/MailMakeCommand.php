<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\MailMakeCommand as BaseMailMakeCommand;

/**
 * @internal
 */
final class MailMakeCommand extends BaseMailMakeCommand
{
    use NamespacesViews;
}
