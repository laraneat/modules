<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Module;

abstract class BaseCommand extends Command
{
    protected FileRepository $modules;

    public function __construct()
    {
        parent::__construct();

        $this->modules = $this->laravel['modules'];
    }

    protected function findModuleOrFail(Module|string $module): Module
    {
        return $module instanceof Module
            ? $module
            : $this->modules->findOrFail($module);
    }
}
