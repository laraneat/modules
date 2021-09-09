<?php

namespace Laraneat\Modules\Exceptions;

class InvalidAssetPath extends \Exception
{
    public static function missingModuleName($asset): InvalidAssetPath
    {
        return new static("Module name was not specified in asset [$asset].");
    }
}
