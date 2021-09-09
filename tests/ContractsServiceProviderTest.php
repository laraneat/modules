<?php

namespace Laraneat\Modules\Tests;

use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Laravel\LaravelFileRepository;

class ContractsServiceProviderTest extends BaseTestCase
{
    /** @test */
    public function it_binds_repository_interface_with_implementation()
    {
        $this->assertInstanceOf(LaravelFileRepository::class, app(RepositoryInterface::class));
    }
}
