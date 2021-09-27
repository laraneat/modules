<?php

namespace Laraneat\Modules\Exceptions;

use InvalidArgumentException;

class InvalidPath extends InvalidArgumentException
{
    public static function make($path): InvalidPath
    {
        return new static(
            sprintf(
                'Path %s is invalid.',
                is_object($path)
                    ? sprintf('class `%s`', get_class($path))
                    : sprintf('type `%s`', gettype($path))
            )
        );
    }
}
