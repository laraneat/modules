<?php

namespace Laraneat\Modules\Tests\Commands;

use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;

/**
 * @group command
 */
class ListCommandTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => ['Article']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        parent::tearDown();
    }

    /** @test */
    public function it_can_list_modules()
    {
        $code = $this->artisan('module:list');

        // We just want to make sure nothing throws an exception inside the list command
        $this->assertTrue(true);
        $this->assertSame(0, $code);
    }
}
