<?php

namespace Laraneat\Modules\Exceptions;

class InvalidTableName extends \Exception
{
    public static function make(string $name): static
    {
        return new static(
            sprintf(
                "The table name '%s' is not valid. " .
                "It must contain only letters, numbers, and underscores, and must start with a letter or underscore.",
                $name,
            )
        );
    }
}
