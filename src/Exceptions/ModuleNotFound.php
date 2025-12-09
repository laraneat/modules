<?php

namespace Laraneat\Modules\Exceptions;

class ModuleNotFound extends \Exception
{
    public static function make(string $packageName): static
    {
        return new static(
            sprintf(
                "Module with '%s' package name does not exist!",
                $packageName,
            )
        );
    }

    public static function makeForName(string $name): static
    {
        return new static(
            sprintf(
                "Module with '%s' name does not exist!",
                $name,
            )
        );
    }

    public static function makeForNameOrPackageName(string $nameOrPackageName): static
    {
        return new static(
            sprintf(
                "Module with '%s' name or package name does not exist!",
                $nameOrPackageName,
            )
        );
    }
}
