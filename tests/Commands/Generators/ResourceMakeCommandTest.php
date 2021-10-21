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
class ResourceMakeCommandTest extends BaseTestCase
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
    public function it_generates_resource_file()
    {
        $code = $this->artisan('module:make:resource', [
            'name' => 'MyAwesomeResource',
            'module' => 'Article',
            '-n' => true
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/Resources/MyAwesomeResource.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_single_resource_file_with_content()
    {
        $code = $this->artisan('module:make:resource', [
            'name' => 'Foo/Bar\\MyAwesomeSingleResource',
            'module' => 'Article',
            '--stub' => 'single'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Resources/Foo/Bar/MyAwesomeSingleResource.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_collection_resource_file_with_content()
    {
        $code = $this->artisan('module:make:resource', [
            'name' => 'Foo/Bar\\MyAwesomeCollectionResource',
            'module' => 'Article',
            '--stub' => 'collection'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Resources/Foo/Bar/MyAwesomeCollectionResource.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_resource_file()
    {
        $this->app['config']->set('modules.generator.components.api-resource.path', 'Foo/Bar\\Resources');

        $code = $this->artisan('module:make:resource', [
            'name' => 'Baz\\Bat/MyAwesomeResource',
            'module' => 'Article',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Resources/Baz/Bat/MyAwesomeResource.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_resource_file()
    {
        $this->app['config']->set('modules.generator.components.api-resource.namespace', 'Foo/Bar\\Resources/');

        $code = $this->artisan('module:make:resource', [
            'name' => 'Baz\\Bat/MyAwesomeResource',
            'module' => 'Article',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Resources/Baz/Bat/MyAwesomeResource.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
