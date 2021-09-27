<?php

namespace Laraneat\Modules\Tests\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Exception\InvalidOptionException;

/**
 * @group command
 * @group generator
 */
class ActionMakeCommandTest extends BaseTestCase
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
    public function it_generates_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'MyAwesomeAction',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Actions/MyAwesomeAction.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_action_file_with_content()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'MyAwesomeAction',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/MyAwesomeAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path()
    {
        $this->app['config']->set('modules.generator.components.action.path', 'Foo/Bar\\NewActions');

        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeAction',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewActions/Baz/Bat/MyAwesomeAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.generator.components.action.namespace', 'Foo/Bar\\NewActions/');

        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeAction',
            'module' => 'Article',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_create_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeCreateAction',
            'module' => 'Article',
            '--stub' => 'create',
            '--model' => 'Bar/TestModel',
            '--request' => 'Bat/TestRequest',
            '--resource' => 'Baz\\TestResource',
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeCreateAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_throws_exception_when_classes_not_provided_for_create_action_file()
    {
        $this->expectException(InvalidOptionException::class);

        $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeCreateAction',
            'module' => 'Article',
            '--stub' => 'create',
            '-n' => '',
        ]);
    }

    /** @test */
    public function it_can_generate_delete_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeDeleteAction',
            'module' => 'Article',
            '--stub' => 'delete',
            '--model' => 'Bar/TestModel',
            '--request' => 'Bat/TestRequest',
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeDeleteAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_list_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeListAction',
            'module' => 'Article',
            '--stub' => 'list',
            '--model' => 'Bar/TestModel',
            '--request' => 'Bat/TestRequest',
            '--resource' => 'Baz\\TestResource',
            '--wizard' => 'Bat\\TestQueryWizard',
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeListAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_update_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeUpdateAction',
            'module' => 'Article',
            '--stub' => 'update',
            '--model' => 'Bar/TestModel',
            '--request' => 'Bat/TestRequest',
            '--resource' => 'Baz\\TestResource',
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeUpdateAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_view_action_file()
    {
        $code = $this->artisan('module:make:action', [
            'name' => 'Baz\\Bat/MyAwesomeViewAction',
            'module' => 'Article',
            '--stub' => 'view',
            '--model' => 'Bar/TestModel',
            '--request' => 'Bat/TestRequest',
            '--resource' => 'Baz\\TestResource',
            '--wizard' => 'Bat\\TestQueryWizard',
        ]);

        $file = $this->finder->get($this->modulePath . '/Actions/Baz/Bat/MyAwesomeViewAction.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
