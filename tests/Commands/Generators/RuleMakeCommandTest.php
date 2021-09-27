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
class RuleMakeCommandTest extends BaseTestCase
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
    public function it_generates_rule_file()
    {
        $code = $this->artisan('module:make:rule', [
            'name' => 'MyAwesomeRule',
            'module' => 'Article',
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Rules/MyAwesomeRule.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_rule_file_with_content()
    {
        $code = $this->artisan('module:make:rule', [
            'name' => 'Foo/Bar\\MyAwesomeRule',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Rules/Foo/Bar/MyAwesomeRule.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_rule_file()
    {
        $this->app['config']->set('modules.generator.components.rule.path', 'Foo/Bar\\Rules');

        $code = $this->artisan('module:make:rule', [
            'name' => 'Baz\\Bat/MyAwesomeRule',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Rules/Baz/Bat/MyAwesomeRule.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_rule_file()
    {
        $this->app['config']->set('modules.generator.components.rule.namespace', 'Foo/Bar\\Rules/');

        $code = $this->artisan('module:make:rule', [
            'name' => 'Baz\\Bat/MyAwesomeRule',
            'module' => 'Article',
        ]);

        $file = $this->finder->get($this->modulePath . '/Rules/Baz/Bat/MyAwesomeRule.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
