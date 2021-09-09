<?php

namespace Nwidart\Modules\Contracts;

interface RunnableInterface
{
    /**
     * Run the specified command.
     *
     * @param string $command
     */
    public function run(string $command);
}
