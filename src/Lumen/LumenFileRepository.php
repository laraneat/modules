<?php

namespace Laraneat\Modules\Lumen;

use Laraneat\Modules\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     *
     * @see FileRepository::createModule()
     */
    protected function createModule(...$args): Module
    {
        return new Module(...$args);
    }
}
