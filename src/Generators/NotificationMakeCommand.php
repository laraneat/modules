<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Foundation\Console\NotificationMakeCommand as BaseNotificationMakeCommand;

/**
 * @internal
 */
final class NotificationMakeCommand extends BaseNotificationMakeCommand
{
    use NamespacesViews;
}
