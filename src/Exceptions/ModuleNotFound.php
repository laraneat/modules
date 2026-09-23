<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use InvalidArgumentException;

final class ModuleNotFound extends InvalidArgumentException implements ModulesException
{
    public static function named(string $name): self
    {
        return new self("Module [{$name}] not found.");
    }
}
