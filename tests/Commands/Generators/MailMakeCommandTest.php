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
class MailMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_mail_file()
    {
        $code = $this->artisan('module:make:mail', [
            'name' => 'MyAwesomePlainMail',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Mails/MyAwesomePlainMail.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_queued_mail_file()
    {
        $code = $this->artisan('module:make:mail', [
            'name' => 'MyAwesomeQueuedMail',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Mails/MyAwesomeQueuedMail.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_mail_file_with_content()
    {
        $code = $this->artisan('module:make:mail', [
            'name' => 'Foo/Bar\\MyAwesomePlainMail',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Mails/Foo/Bar/MyAwesomePlainMail.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_queued_mail_file_with_content()
    {
        $code = $this->artisan('module:make:mail', [
            'name' => 'Foo/Bar\\MyAwesomeQueuedMail',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $file = $this->finder->get($this->modulePath . '/Mails/Foo/Bar/MyAwesomeQueuedMail.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_mail_file()
    {
        $this->app['config']->set('modules.generator.components.mail.path', 'Foo/Bar\\Mails');

        $code = $this->artisan('module:make:mail', [
            'name' => 'Baz\\Bat/MyAwesomePlainMail',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Mails/Baz/Bat/MyAwesomePlainMail.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_mail_file()
    {
        $this->app['config']->set('modules.generator.components.mail.namespace', 'Foo/Bar\\Mails/');

        $code = $this->artisan('module:make:mail', [
            'name' => 'Baz\\Bat/MyAwesomePlainMail',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Mails/Baz/Bat/MyAwesomePlainMail.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
