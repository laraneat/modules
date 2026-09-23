<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use RuntimeException;

final class InvalidTemplate extends RuntimeException implements ModulesException
{
    public static function at(string $file, string $reason): self
    {
        return new self("Invalid module template [{$file}]: {$reason}");
    }
}
