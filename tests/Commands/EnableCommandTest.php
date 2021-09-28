<?php

namespace Laraneat\Modules\Tests\Commands;

use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Module;
use Laraneat\Modules\Tests\BaseTestCase;

/**
 * @group command
 */
class EnableCommandTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => 'Article']);
        $this->artisan('module:make', ['name' => 'Taxonomy']);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        $this->app[RepositoryInterface::class]->delete('Taxonomy');
        parent::tearDown();
    }

    /** @test */
    public function it_enables_a_module()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Article');
        $blogModule->disable();

        $code = $this->artisan('module:enable', ['module' => 'Article']);

        $this->assertTrue($blogModule->isEnabled());
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_enables_all_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Article');
        $blogModule->disable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
        $taxonomyModule->disable();

        $code = $this->artisan('module:enable');

        $this->assertTrue($blogModule->isEnabled() && $taxonomyModule->isEnabled());
        $this->assertSame(0, $code);
    }
}
