<?php

namespace Laraneat\Modules\Traits;

use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;

/**
 * @mixin \Illuminate\Support\ServiceProvider
 */
trait ModuleCommandTrait
{
    /**
     * Get the module.
     *
     * @return Module
     */
    public function getModule(): Module
    {
        $moduleName = $this->argument('module');

        return $moduleName ? Modules::findOrFail($moduleName) : Modules::getUsedNow();
    }
}
