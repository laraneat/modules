<?php

namespace Laraneat\Modules\Exceptions;

class InvalidAssetPath extends \Exception
{
    public static function missingModuleName(string $asset): InvalidAssetPath
    {
        return new InvalidAssetPath("Module name was not specified in asset [$asset].");
    }
}
