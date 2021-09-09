<?php

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraneat\Modules\Module
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
