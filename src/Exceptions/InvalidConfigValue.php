<?php

namespace Laraneat\Modules\Exceptions;

class InvalidConfigValue extends \Exception
{
    public static function make(string $configPath): static
    {
        return new static(
            sprintf(
                "Config value '%s' is invalid.",
                $configPath
            )
        );
    }

    public static function makeForNullValue(string $configPath): static
    {
        return new static(
            sprintf(
                "Config value '%s' cannot be null.",
                $configPath
            )
        );
    }
}
