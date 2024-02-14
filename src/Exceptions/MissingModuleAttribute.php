<?php

namespace Laraneat\Modules\Exceptions;

class MissingModuleAttribute extends \Exception
{
    public static function make(string $attributeName, string $packageName): static
    {
        return new static(
            sprintf(
                "The '%s' attribute for the '%s' package of laraneat module is not defined, add this attribute to ['extra']['laraneat']['module']['%s'] in module composer.json",
                $attributeName,
                $packageName,
                $attributeName
            )
        );
    }
}
