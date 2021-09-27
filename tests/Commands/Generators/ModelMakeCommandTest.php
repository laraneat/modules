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
class ModelMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_model_file()
    {
        $code = $this->artisan('module:make:model', [
            'name' => 'MyAwesomePlainModel',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Models/MyAwesomePlainModel.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_model_file_with_content()
    {
        $code = $this->artisan('module:make:model', [
            'name' => 'Foo/Bar\\MyAwesomePlainModel',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Models/Foo/Bar/MyAwesomePlainModel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_full_model_file_with_content()
    {
        $code = $this->artisan('module:make:model', [
            'name' => 'Foo/Bar\\MyAwesomeFullModel',
            'module' => 'Article',
            '--stub' => 'full',
            '--factory' => 'Bar\\Baz/Bat\\MyAwesomeFactory'
        ]);

        $file = $this->finder->get($this->modulePath . '/Models/Foo/Bar/MyAwesomeFullModel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_model_file()
    {
        $this->app['config']->set('modules.generator.components.model.path', 'Foo/Bar\\Models');

        $code = $this->artisan('module:make:model', [
            'name' => 'Baz\\Bat/MyAwesomePlainModel',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Models/Baz/Bat/MyAwesomePlainModel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_model_file()
    {
        $this->app['config']->set('modules.generator.components.model.namespace', 'Foo/Bar\\Models/');

        $code = $this->artisan('module:make:model', [
            'name' => 'Baz\\Bat/MyAwesomePlainModel',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Models/Baz/Bat/MyAwesomePlainModel.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
