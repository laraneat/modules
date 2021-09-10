<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Support\Str;

class HelpersTest extends BaseTestCase
{
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
        $this->modulePath = base_path('app/Modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_finds_the_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog'), 'app/Modules/Blog'));
    }

    /** @test */
    public function it_can_bind_a_relative_path_to_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog', 'config/config.php'), 'app/Modules/Blog/config/config.php'));
    }
}
