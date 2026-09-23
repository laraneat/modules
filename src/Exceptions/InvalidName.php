<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use InvalidArgumentException;

final class InvalidName extends InvalidArgumentException implements ModulesException
{
    public static function of(string $kind, string $name, string $rule): self
    {
        return new self("Invalid {$kind} [{$name}]: {$rule}");
    }
}
