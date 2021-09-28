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
class JobMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_job_file()
    {
        $code = $this->artisan('module:make:job', [
            'name' => 'MyAwesomePlainJob',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Jobs/MyAwesomePlainJob.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_queued_job_file()
    {
        $code = $this->artisan('module:make:job', [
            'name' => 'MyAwesomeQueuedJob',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Jobs/MyAwesomeQueuedJob.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_job_file_with_content()
    {
        $code = $this->artisan('module:make:job', [
            'name' => 'Foo/Bar\\MyAwesomePlainJob',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Jobs/Foo/Bar/MyAwesomePlainJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_queued_job_file_with_content()
    {
        $code = $this->artisan('module:make:job', [
            'name' => 'Foo/Bar\\MyAwesomeQueuedJob',
            'module' => 'Article',
            '--stub' => 'queued'
        ]);

        $file = $this->finder->get($this->modulePath . '/Jobs/Foo/Bar/MyAwesomeQueuedJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_job_file()
    {
        $this->app['config']->set('modules.generator.components.job.path', 'Foo/Bar\\Jobs');

        $code = $this->artisan('module:make:job', [
            'name' => 'Baz\\Bat/MyAwesomePlainJob',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Jobs/Baz/Bat/MyAwesomePlainJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_job_file()
    {
        $this->app['config']->set('modules.generator.components.job.namespace', 'Foo/Bar\\Jobs/');

        $code = $this->artisan('module:make:job', [
            'name' => 'Baz\\Bat/MyAwesomePlainJob',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Jobs/Baz/Bat/MyAwesomePlainJob.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
