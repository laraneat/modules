<?php

declare(strict_types=1);

namespace Laraneat\Modules\Exceptions;

use RuntimeException;
use Throwable;

final class ComposerFailed extends RuntimeException implements ModulesException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
