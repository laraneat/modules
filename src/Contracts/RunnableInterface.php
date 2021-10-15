<?php

namespace Laraneat\Modules\Contracts;

interface RunnableInterface
{
    /**
     * Run the specified command.
     */
    public function run(string $command);
}
