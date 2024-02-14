<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Traits\ModuleCommandHelpersTrait;

abstract class BaseCommand extends Command
{
    use ModuleCommandHelpersTrait;

    public function __construct(protected ModulesRepository $modulesRepository)
    {
        parent::__construct();
    }
}
