<?php

namespace Laraneat\Modules\Exceptions;

class InvalidClassName extends \Exception
{
    public static function make(string $name): static
    {
        return new static(
            sprintf(
                "The class name '%s' is not a valid PHP class name. " .
                "It must start with a letter or underscore, followed by letters, numbers, or underscores.",
                $name,
            )
        );
    }
}
