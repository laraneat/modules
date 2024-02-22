<?php

namespace Laraneat\Modules\Exceptions;

class FileAlreadyExist extends \Exception
{
    public static function make(string $message = 'File already exists!'): static
    {
        return new static($message);
    }
}
