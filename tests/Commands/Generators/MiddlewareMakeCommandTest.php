<?php

namespace Laraneat\Modules\Tests\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @group command
 * @group generator
 */
class MiddlewareMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Article'], '--plain' => true]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_middleware_file()
    {
        $code = $this->artisan('module:make:middleware', [
            'name' => 'MyAwesomeMiddleware',
            'module' => 'Article',
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Middleware/MyAwesomeMiddleware.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_middleware_file_with_content()
    {
        $code = $this->artisan('module:make:middleware', [
            'name' => 'Foo/Bar\\MyAwesomeMiddleware',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Middleware/Foo/Bar/MyAwesomeMiddleware.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_middleware_file()
    {
        $this->app['config']->set('modules.generator.components.middleware.path', 'Foo/Bar\\Middleware');

        $code = $this->artisan('module:make:middleware', [
            'name' => 'Baz\\Bat/MyAwesomeMiddleware',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Middleware/Baz/Bat/MyAwesomeMiddleware.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_middleware_file()
    {
        $this->app['config']->set('modules.generator.components.middleware.namespace', 'Foo/Bar\\Middleware/');

        $code = $this->artisan('module:make:middleware', [
            'name' => 'Baz\\Bat/MyAwesomeMiddleware',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Middleware/Baz/Bat/MyAwesomeMiddleware.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
