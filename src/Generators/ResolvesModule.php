<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Laraneat\Modules\Module;

/**
 * @internal
 */
trait ResolvesModule
{
    /**
     * The module the command generates into, if it runs with "--module".
     */
    protected function currentModule(): ?Module
    {
        return $this->laravel->make(ModuleContext::class)->module();
    }
}
