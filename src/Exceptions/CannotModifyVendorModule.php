<?php

namespace Laraneat\Modules\Exceptions;

class CannotModifyVendorModule extends \Exception
{
    public static function make(string $packageName): static
    {
        return new static(
            sprintf(
                "Cannot modify vendor module '%s'!",
                $packageName,
            )
        );
    }
}
