<?php

namespace Laraneat\Modules\Process;

use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Contracts\RunnableInterface;

class Runner implements RunnableInterface
{
    /**
     * The module instance.
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Run the given command.
     *
     * @param string $command
     */
    public function run(string $command): void
    {
        passthru($command);
    }
}
