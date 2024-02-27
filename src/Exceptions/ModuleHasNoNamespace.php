<?php

namespace Laraneat\Modules\Exceptions;

class ModuleHasNoNamespace extends \Exception
{
    public static function make(string $packageName): static
    {
        return new static(
            sprintf(
                "No namespace specified for module '%s', add namespace to ['autoload']['psr-4'] in module composer.json",
                $packageName,
            )
        );
    }
}
