<?php

namespace Laraneat\Modules\Tests\Commands;

use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FactoryMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /** @test */
    public function it_makes_factory()
    {
        $code = $this->artisan('module:make-factory', ['name' => 'Post', 'module' => 'Blog']);

        $factoryFile = $this->modulePath . '/Database/factories/PostFactory.php';

        $this->assertTrue(is_file($factoryFile), 'Factory file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($factoryFile));
        $this->assertSame(0, $code);
    }
}
