<?php

namespace Laraneat\Modules\Exceptions;

use InvalidArgumentException;
use Laraneat\Modules\Generators\ModuleGenerator;

class InvalidModulePreset extends InvalidArgumentException
{
    public static function make(string $preset): static
    {
        return new static(
            sprintf(
                "Module preset '%s' is invalid, only %s are available",
                $preset,
                implode(', ', ModuleGenerator::AVAILABLE_PRESETS)
            )
        );
    }
}
