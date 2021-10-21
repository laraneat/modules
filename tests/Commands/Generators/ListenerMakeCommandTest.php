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
class ListenerMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_listener_file()
    {
        $code = $this->artisan('module:make:listener', [
            'name' => 'MyAwesomePlainListener',
            'module' => 'Article',
            '--stub' => 'plain',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Listeners/MyAwesomePlainListener.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_queued_listener_file()
    {
        $code = $this->artisan('module:make:listener', [
            'name' => 'MyAwesomeQueuedListener',
            'module' => 'Article',
            '--stub' => 'queued',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Listeners/MyAwesomeQueuedListener.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_listener_file_with_content()
    {
        $code = $this->artisan('module:make:listener', [
            'name' => 'Foo/Bar\\MyAwesomePlainListener',
            'module' => 'Article',
            '--stub' => 'plain',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $file = $this->finder->get($this->modulePath . '/Listeners/Foo/Bar/MyAwesomePlainListener.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_queued_listener_file_with_content()
    {
        $code = $this->artisan('module:make:listener', [
            'name' => 'Foo/Bar\\MyAwesomeQueuedListener',
            'module' => 'Article',
            '--stub' => 'queued',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $file = $this->finder->get($this->modulePath . '/Listeners/Foo/Bar/MyAwesomeQueuedListener.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_listener_file()
    {
        $this->app['config']->set('modules.generator.components.listener.path', 'Foo/Bar\\Listeners');

        $code = $this->artisan('module:make:listener', [
            'name' => 'Baz\\Bat/MyAwesomePlainListener',
            'module' => 'Article',
            '--stub' => 'plain',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Listeners/Baz/Bat/MyAwesomePlainListener.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_listener_file()
    {
        $this->app['config']->set('modules.generator.components.listener.namespace', 'Foo/Bar\\Listeners/');

        $code = $this->artisan('module:make:listener', [
            'name' => 'Baz\\Bat/MyAwesomePlainListener',
            'module' => 'Article',
            '--stub' => 'plain',
            '--event' => 'Foo\\Bar/MyAwesomeEvent'
        ]);

        $file = $this->finder->get($this->modulePath . '/Listeners/Baz/Bat/MyAwesomePlainListener.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
