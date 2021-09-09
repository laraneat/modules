<?php

namespace Nwidart\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nwidart\Modules\Module
 */
class Module extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
