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
class EventMakeCommandTest extends BaseTestCase
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
    public function it_generates_event_file()
    {
        $code = $this->artisan('module:make:event', [
            'name' => 'MyAwesomeEvent',
            'module' => 'Article'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Events/MyAwesomeEvent.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_event_file_with_content()
    {
        $code = $this->artisan('module:make:event', [
            'name' => 'Foo/Bar\\MyAwesomeEvent',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/Events/Foo/Bar/MyAwesomeEvent.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_event_file()
    {
        $this->app['config']->set('modules.generator.components.event.path', 'Foo/Bar\\Events');

        $code = $this->artisan('module:make:event', [
            'name' => 'Baz\\Bat/MyAwesomeEvent',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Events/Baz/Bat/MyAwesomeEvent.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_event_file()
    {
        $this->app['config']->set('modules.generator.components.event.namespace', 'Foo/Bar\\Events/');

        $code = $this->artisan('module:make:event', [
            'name' => 'Baz\\Bat/MyAwesomeEvent',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/Events/Baz/Bat/MyAwesomeEvent.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
