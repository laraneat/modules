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
class MigrationMakeCommandTest extends BaseTestCase
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
    public function it_generates_migration_file()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'create_posts_table',
            'module' => 'Article'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.generator.components.migration.path', 'Foo/Bar\\Migrations');

        $code = $this->artisan('module:make:migration', [
            'name' => 'create_posts_table',
            'module' => 'Article'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Foo/Bar/Migrations');

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_correct_create_migration_file_content()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'create_posts_table',
            'module' => 'Article',
            '--fields' => 'title:string,excerpt:text,content:text,belongsTo:user:id:users'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $fileName = $files[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_correct_add_migration_file_content()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'add_title_to_posts_table',
            'module' => 'Article',
            '--fields' => 'title:string'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $fileName = $files[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_correct_delete_migration_file_content()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'remove_title_from_posts_table',
            'module' => 'Article',
            '--fields' => 'title:string'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $fileName = $files[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_correct_pivot_migration_file_content()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'create_user_role_table',
            'module' => 'Article',
            '--stub' => 'pivot',
            '-n' => true
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $fileName = $files[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
//    public function it_generates_correct_pivot_migration_file_content_with_options()
//    {
//        $code = $this->artisan('module:make:migration', [
//            'name' => 'create_user_role_table',
//            'module' => 'Article',
//            '--stub' => 'pivot',
//            '--tableOne' => 'users',
//            '--tableTwo' => 'roles',
//        ]);
//
//        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
//        $fileName = $files[0]->getRelativePathname();
//        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);
//
//        $this->assertMatchesSnapshot($file);
//        $this->assertSame(0, $code);
//    }

    /** @test */
    public function it_generates_correct_plain_migration_file_content()
    {
        $code = $this->artisan('module:make:migration', [
            'name' => 'create_posts_table',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $files = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $fileName = $files[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath . '/Data/Migrations/' . $fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
