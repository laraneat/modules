<?php

namespace Nwidart\Modules\Process;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Contracts\RunnableInterface;

class Runner implements RunnableInterface
{
    /**
     * The module instance.
     * @var RepositoryInterface
     */
    protected $module;

    public function __construct(RepositoryInterface $module)
    {
        $this->module = $module;
    }

    /**
     * Run the given command.
     *
     * @param string $command
     */
    public function run(string $command)
    {
        passthru($command);
    }
}
