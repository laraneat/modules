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
class ProviderMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => 'Article', '--plain' => true]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_provider_file()
    {
        $code = $this->artisan('module:make:provider', [
            'name' => 'MyAwesomeProvider',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Providers/MyAwesomeProvider.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_provider_file_with_content()
    {
        $code = $this->artisan('module:make:provider', [
            'name' => 'MyAwesomeProvider',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Providers/MyAwesomeProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.generator.components.provider.path', 'Foo/Bar\\NewProviders');

        $code = $this->artisan('module:make:provider', [
            'name' => 'Baz\\Bat/MyAwesomeProvider',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewProviders/Baz/Bat/MyAwesomeProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.generator.components.provider.namespace', 'Foo/Bar\\NewProviders/');

        $code = $this->artisan('module:make:provider', [
            'name' => 'Baz\\Bat/MyAwesomeProvider',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Providers/Baz/Bat/MyAwesomeProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_module_provider_file()
    {
        $code = $this->artisan('module:make:provider', [
            'name' => 'Baz\\Bat/MyAwesomeModuleProvider',
            'module' => 'Article',
            '--stub' => 'module',
        ]);

        $file = $this->finder->get($this->modulePath . '/Providers/Baz/Bat/MyAwesomeModuleProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_route_provider_file()
    {
        $code = $this->artisan('module:make:provider', [
            'name' => 'Baz\\Bat/MyAwesomeRouteProvider',
            'module' => 'Article',
            '--stub' => 'route',
        ]);

        $file = $this->finder->get($this->modulePath . '/Providers/Baz/Bat/MyAwesomeRouteProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
