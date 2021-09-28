<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class HelpersTest extends BaseTestCase
{
    private Filesystem $finder;
    private string $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => 'Article']);
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_finds_the_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Article'), 'app/Modules/Article'));
    }

    /** @test */
    public function it_can_bind_a_relative_path_to_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Article', 'config/config.php'), 'app/Modules/Article/config/config.php'));
    }
}
