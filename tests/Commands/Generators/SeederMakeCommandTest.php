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
class SeederMakeCommandTest extends BaseTestCase
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
    public function it_generates_plain_seeder_file()
    {
        $code = $this->artisan('module:make:seeder', [
            'name' => 'MyAwesomePlainSeeder',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Data/Seeders/MyAwesomePlainSeeder.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_permissions_seeder_file()
    {
        $code = $this->artisan('module:make:seeder', [
            'name' => 'MyAwesomePermissionsSeeder',
            'module' => 'Article',
            '--stub' => 'permissions',
            '--model' => 'Bar\\Bat/Baz\\MyAwesomeModel'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Data/Seeders/MyAwesomePermissionsSeeder.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_plain_seeder_file_with_content()
    {
        $code = $this->artisan('module:make:seeder', [
            'name' => 'Foo/Bar\\MyAwesomePlainSeeder',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Data/Seeders/Foo/Bar/MyAwesomePlainSeeder.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_permissions_seeder_file_with_content()
    {
        $code = $this->artisan('module:make:seeder', [
            'name' => 'Foo/Bar\\MyAwesomePermissionsSeeder',
            'module' => 'Article',
            '--stub' => 'permissions',
            '--model' => 'Bar\\Bat/Baz\\MyAwesomeModel'
        ]);

        $file = $this->finder->get($this->modulePath . '/Data/Seeders/Foo/Bar/MyAwesomePermissionsSeeder.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_plain_seeder_file()
    {
        $this->app['config']->set('modules.generator.components.seeder.path', 'Foo/Bar\\Seeders');

        $code = $this->artisan('module:make:seeder', [
            'name' => 'Baz\\Bat/MyAwesomePlainSeeder',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Seeders/Baz/Bat/MyAwesomePlainSeeder.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_plain_seeder_file()
    {
        $this->app['config']->set('modules.generator.components.seeder.namespace', 'Foo/Bar\\Seeders/');

        $code = $this->artisan('module:make:seeder', [
            'name' => 'Baz\\Bat/MyAwesomePlainSeeder',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Data/Seeders/Baz/Bat/MyAwesomePlainSeeder.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
