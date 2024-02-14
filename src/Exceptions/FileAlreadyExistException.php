<?php

namespace Laraneat\Modules\Exceptions;

class FileAlreadyExistException extends \Exception
{
    public static function make(string $message = 'File already exists!'): static
    {
        return new static($message);
    }
}
