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
class PolicyMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_policy_file()
    {
        $code = $this->artisan('module:make:policy', [
            'name' => 'MyAwesomePlainPolicy',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Policies/MyAwesomePlainPolicy.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_full_policy_file()
    {
        $code = $this->artisan('module:make:policy', [
            'name' => 'MyAwesomeFullPolicy',
            'module' => 'Article',
            '--stub' => 'full',
            '--model' => 'Bar\\Bat/Baz\\MyAwesomeModel'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Policies/MyAwesomeFullPolicy.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_policy_file_with_content()
    {
        $code = $this->artisan('module:make:policy', [
            'name' => 'Foo/Bar\\MyAwesomePlainPolicy',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Policies/Foo/Bar/MyAwesomePlainPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_full_policy_file_with_content()
    {
        $code = $this->artisan('module:make:policy', [
            'name' => 'Foo/Bar\\MyAwesomeFullPolicy',
            'module' => 'Article',
            '--stub' => 'full',
            '--model' => 'Bar\\Bat/Baz\\MyAwesomeModel'
        ]);

        $file = $this->finder->get($this->modulePath . '/Policies/Foo/Bar/MyAwesomeFullPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_policy_file()
    {
        $this->app['config']->set('modules.generator.components.policy.path', 'Foo/Bar\\Policies');

        $code = $this->artisan('module:make:policy', [
            'name' => 'Baz\\Bat/MyAwesomePlainPolicy',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Policies/Baz/Bat/MyAwesomePlainPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_policy_file()
    {
        $this->app['config']->set('modules.generator.components.policy.namespace', 'Foo/Bar\\Policies/');

        $code = $this->artisan('module:make:policy', [
            'name' => 'Baz\\Bat/MyAwesomePlainPolicy',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Policies/Baz/Bat/MyAwesomePlainPolicy.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
