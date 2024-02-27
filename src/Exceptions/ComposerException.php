<?php

namespace Laraneat\Modules\Exceptions;

class ComposerException extends \Exception
{
    public static function make(string $message): static
    {
        return new static($message);
    }
}
