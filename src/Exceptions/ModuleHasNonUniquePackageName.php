<?php

namespace Laraneat\Modules\Exceptions;

class ModuleHasNonUniquePackageName extends \Exception
{
    public static function make(string $packageName): static
    {
        return new static(
            sprintf(
                "2 or more modules have the same package name '%s'",
                $packageName,
            )
        );
    }
}
