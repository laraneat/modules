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
class ObserverMakeCommandTest extends BaseTestCase
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
    public function it_generates_observer_file()
    {
        $code = $this->artisan('module:make:observer', [
            'name' => 'MyAwesomeObserver',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Observers/MyAwesomeObserver.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_observer_file_with_content()
    {
        $code = $this->artisan('module:make:observer', [
            'name' => 'Foo/Bar\\MyAwesomeObserver',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Observers/Foo/Bar/MyAwesomeObserver.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_observer_file()
    {
        $this->app['config']->set('modules.generator.components.observer.path', 'Foo/Bar\\Observers');

        $code = $this->artisan('module:make:observer', [
            'name' => 'Baz\\Bat/MyAwesomeObserver',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Observers/Baz/Bat/MyAwesomeObserver.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_observer_file()
    {
        $this->app['config']->set('modules.generator.components.observer.namespace', 'Foo/Bar\\Observers/');

        $code = $this->artisan('module:make:observer', [
            'name' => 'Baz\\Bat/MyAwesomeObserver',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Observers/Baz/Bat/MyAwesomeObserver.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
