<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidModule extends RuntimeException implements ModulesException
{
    public static function at(string $path, string $reason, ?Throwable $previous = null): self
    {
        return new self("Invalid module [{$path}]: {$reason}", previous: $previous);
    }
}
