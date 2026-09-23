<?php

namespace Laraneat\Modules\Exceptions;

class CannotDeleteModule extends \Exception
{
    public static function becauseItIsSymlink(string $packageName, string $path): static
    {
        return new static(
            sprintf(
                "Module '%s' is a symbolic link (%s). Remove the link manually instead of deleting its target.",
                $packageName,
                $path,
            )
        );
    }
}
