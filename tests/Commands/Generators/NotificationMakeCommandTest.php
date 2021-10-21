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
class NotificationMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_notification_file()
    {
        $code = $this->artisan('module:make:notification', [
            'name' => 'MyAwesomePlainNotification',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Notifications/MyAwesomePlainNotification.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_queued_notification_file()
    {
        $code = $this->artisan('module:make:notification', [
            'name' => 'MyAwesomeQueuedNotification',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Notifications/MyAwesomeQueuedNotification.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_notification_file_with_content()
    {
        $code = $this->artisan('module:make:notification', [
            'name' => 'Foo/Bar\\MyAwesomePlainNotification',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Notifications/Foo/Bar/MyAwesomePlainNotification.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_queued_notification_file_with_content()
    {
        $code = $this->artisan('module:make:notification', [
            'name' => 'Foo/Bar\\MyAwesomeQueuedNotification',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $file = $this->finder->get($this->modulePath . '/Notifications/Foo/Bar/MyAwesomeQueuedNotification.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_notification_file()
    {
        $this->app['config']->set('modules.generator.components.notification.path', 'Foo/Bar\\Notifications');

        $code = $this->artisan('module:make:notification', [
            'name' => 'Baz\\Bat/MyAwesomePlainNotification',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Notifications/Baz/Bat/MyAwesomePlainNotification.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_notification_file()
    {
        $this->app['config']->set('modules.generator.components.notification.namespace', 'Foo/Bar\\Notifications/');

        $code = $this->artisan('module:make:notification', [
            'name' => 'Baz\\Bat/MyAwesomePlainNotification',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Notifications/Baz/Bat/MyAwesomePlainNotification.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
