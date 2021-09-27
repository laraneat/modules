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
class ExceptionMakeCommandTest extends BaseTestCase
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
    public function it_generates_exception_file()
    {
        $code = $this->artisan('module:make:exception', [
            'name' => 'MyAwesomeException',
            'module' => 'Article',
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Exceptions/MyAwesomeException.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_exception_file_with_content()
    {
        $code = $this->artisan('module:make:exception', [
            'name' => 'Foo/Bar\\MyAwesomeException',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Exceptions/Foo/Bar/MyAwesomeException.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_exception_file()
    {
        $this->app['config']->set('modules.generator.components.exception.path', 'Foo/Bar\\Exceptions');

        $code = $this->artisan('module:make:exception', [
            'name' => 'Baz\\Bat/MyAwesomeException',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Exceptions/Baz/Bat/MyAwesomeException.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_exception_file()
    {
        $this->app['config']->set('modules.generator.components.exception.namespace', 'Foo/Bar\\Exceptions/');

        $code = $this->artisan('module:make:exception', [
            'name' => 'Baz\\Bat/MyAwesomeException',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Exceptions/Baz/Bat/MyAwesomeException.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
