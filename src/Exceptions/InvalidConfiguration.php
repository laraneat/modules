<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use InvalidArgumentException;

final class InvalidConfiguration extends InvalidArgumentException implements ModulesException
{
    public static function because(string $reason): self
    {
        return new self("Invalid config/modules.php: {$reason}");
    }
}
