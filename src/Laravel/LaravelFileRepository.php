<?php

namespace Laraneat\Modules\Laravel;

use Laraneat\Modules\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args): Module
    {
        return new Module(...$args);
    }
}
