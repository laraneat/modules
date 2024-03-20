<?php

namespace Laraneat\Modules\Exceptions;

class NameIsReserved extends \Exception
{
    public static function make(string $name): static
    {
        return new static(
            sprintf(
                "The name '%s' is reserved by PHP.",
                $name,
            )
        );
    }
}
