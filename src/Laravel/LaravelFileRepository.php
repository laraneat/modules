<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\FileRepository;

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
