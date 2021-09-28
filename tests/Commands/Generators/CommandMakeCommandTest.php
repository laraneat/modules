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
class CommandMakeCommandTest extends BaseTestCase
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
    public function it_generates_command_file()
    {
        $code = $this->artisan('module:make:command', [
            'name' => 'MyAwesomeCommand',
            'module' => 'Article',
            '-n' => true,
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/CLI/Commands/MyAwesomeCommand.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_command_file_with_content()
    {
        $code = $this->artisan('module:make:command', [
            'name' => 'Foo/Bar\\MyAwesomeCommand',
            'module' => 'Article',
            '--command' => 'my:awesome:command'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/CLI/Commands/Foo/Bar/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_command_file()
    {
        $this->app['config']->set('modules.generator.components.cli-command.path', 'Foo/Bar\\Commands');

        $code = $this->artisan('module:make:command', [
            'name' => 'Baz\\Bat/MyAwesomeCommand',
            'module' => 'Article',
            '--command' => 'my:awesome:command'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Commands/Baz/Bat/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_command_file()
    {
        $this->app['config']->set('modules.generator.components.cli-command.namespace', 'Foo/Bar\\Commands/');

        $code = $this->artisan('module:make:command', [
            'name' => 'Baz\\Bat/MyAwesomeCommand',
            'module' => 'Article',
            '--command' => 'my:awesome:command'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/CLI/Commands/Baz/Bat/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
