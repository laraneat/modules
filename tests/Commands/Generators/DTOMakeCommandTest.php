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
class DTOMakeCommandTest extends BaseTestCase
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
    public function it_generates_dto_file()
    {
        $code = $this->artisan('module:make:dto', [
            'name' => 'MyAwesomeDTO',
            'module' => 'Article',
        ]);

        $this->assertTrue(is_file($this->modulePath . '/DTO/MyAwesomeDTO.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_dto_file()
    {
        $code = $this->artisan('module:make:dto', [
            'name' => 'Foo/Bar\\MyAwesomeDTO',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/DTO/Foo/Bar/MyAwesomeDTO.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_dto_file_with_strict_flag()
    {
        $code = $this->artisan('module:make:dto', [
            'name' => 'Foo/Bar\\MyAwesomeDTO',
            'module' => 'Article',
            '--strict' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/DTO/Foo/Bar/MyAwesomeDTO.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_dto_file()
    {
        $this->app['config']->set('modules.generator.components.dto.path', 'Foo/Bar\\DTO');

        $code = $this->artisan('module:make:dto', [
            'name' => 'Baz\\Bat/MyAwesomeDTO',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/DTO/Baz/Bat/MyAwesomeDTO.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_dto_file()
    {
        $this->app['config']->set('modules.generator.components.dto.namespace', 'Foo/Bar\\DTO/');

        $code = $this->artisan('module:make:dto', [
            'name' => 'Baz\\Bat/MyAwesomeDTO',
            'module' => 'Article'
        ]);

        $file = $this->finder->get($this->modulePath . '/DTO/Baz/Bat/MyAwesomeDTO.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
