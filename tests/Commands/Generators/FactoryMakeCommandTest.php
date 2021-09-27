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
class FactoryMakeCommandTest extends BaseTestCase
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
    public function it_generates_factory_file()
    {
        $code = $this->artisan('module:make:factory', [
            'name' => 'MyAwesomeFactory',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Data/Factories/MyAwesomeFactory.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_factory_file_with_content()
    {
        $code = $this->artisan('module:make:factory', [
            'name' => 'Foo/Bar\\MyAwesomeFactory',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Data/Factories/Foo/Bar/MyAwesomeFactory.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_factory_file()
    {
        $this->app['config']->set('modules.generator.components.factory.path', 'Foo/Bar\\Factories');

        $code = $this->artisan('module:make:factory', [
            'name' => 'Baz\\Bat/MyAwesomeFactory',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Factories/Baz/Bat/MyAwesomeFactory.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_factory_file()
    {
        $this->app['config']->set('modules.generator.components.factory.namespace', 'Foo/Bar\\Factories/');

        $code = $this->artisan('module:make:factory', [
            'name' => 'Baz\\Bat/MyAwesomeFactory',
            'module' => 'Article',
            '--model' => 'Some\\Nested/Model'
        ]);

        $file = $this->finder->get($this->modulePath . '/Data/Factories/Baz/Bat/MyAwesomeFactory.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
