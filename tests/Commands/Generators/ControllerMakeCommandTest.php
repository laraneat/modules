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
class ControllerMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => 'Article', '--plain' => true]);
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_api_controller_file()
    {
        $code = $this->artisan('module:make:controller', [
            'name' => 'MyAwesomeAPIController',
            'module' => 'Article',
            '--ui' => 'api'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/Controllers/MyAwesomeAPIController.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_web_controller_file()
    {
        $code = $this->artisan('module:make:controller', [
            'name' => 'MyAwesomeWEBController',
            'module' => 'Article',
            '--ui' => 'web'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/WEB/Controllers/MyAwesomeWEBController.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_api_controller_file_with_content()
    {
        $code = $this->artisan('module:make:controller', [
            'name' => 'Foo/Bar\\MyAwesomeAPIController',
            'module' => 'Article',
            '--ui' => 'api'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Controllers/Foo/Bar/MyAwesomeAPIController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_web_controller_file_with_content()
    {
        $code = $this->artisan('module:make:controller', [
            'name' => 'Foo/Bar\\MyAwesomeWEBController',
            'module' => 'Article',
            '--ui' => 'web'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Controllers/Foo/Bar/MyAwesomeWEBController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_api_controller_file()
    {
        $this->app['config']->set('modules.generator.components.api-controller.path', 'Foo/Bar\\Controllers');

        $code = $this->artisan('module:make:controller', [
            'name' => 'Baz\\Bat/MyAwesomeAPIController',
            'module' => 'Article',
            '--ui' => 'api'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Controllers/Baz/Bat/MyAwesomeAPIController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_api_controller_file()
    {
        $this->app['config']->set('modules.generator.components.api-controller.namespace', 'Foo/Bar\\Controllers/');

        $code = $this->artisan('module:make:controller', [
            'name' => 'Baz\\Bat/MyAwesomeAPIController',
            'module' => 'Article',
            '--ui' => 'api'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Controllers/Baz/Bat/MyAwesomeAPIController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_web_controller_file()
    {
        $this->app['config']->set('modules.generator.components.web-controller.path', 'Foo/Bar\\Controllers');

        $code = $this->artisan('module:make:controller', [
            'name' => 'Baz\\Bat/MyAwesomeWEBController',
            'module' => 'Article',
            '--ui' => 'web'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Controllers/Baz/Bat/MyAwesomeWEBController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_web_controller_file()
    {
        $this->app['config']->set('modules.generator.components.web-controller.namespace', 'Foo/Bar\\Controllers/');

        $code = $this->artisan('module:make:controller', [
            'name' => 'Baz\\Bat/MyAwesomeWEBController',
            'module' => 'Article',
            '--ui' => 'web'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Controllers/Baz/Bat/MyAwesomeWEBController.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
