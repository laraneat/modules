<?php

namespace Laraneat\Modules\Exceptions;

class DirectoryMustBePresentAndWritable extends \Exception
{
    public static function make(string $directory): static
    {
        return new static(
            sprintf(
                "The '%s' directory must be present and writable.",
                $directory
            )
        );
    }
}
