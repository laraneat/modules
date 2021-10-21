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
class TestMakeCommandTest extends BaseTestCase
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

    // Type: Unit

    /** @test */
    public function it_generate_unit_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeUnitTest',
            'module' => 'Article',
            '--type' => 'unit',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Tests/Unit/MyAwesomeUnitTest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_unit_test_file_with_content()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeUnitTest',
            'module' => 'Article',
            '--type' => 'unit',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Tests/Unit/MyAwesomeUnitTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_unit_test()
    {
        $this->app['config']->set('modules.generator.components.unit-test.path', 'Foo/Bar\\NewTests');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeUnitTest',
            'module' => 'Article',
            '--type' => 'unit',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewTests/Baz/Bat/MyAwesomeUnitTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_unit_test()
    {
        $this->app['config']->set('modules.generator.components.unit-test.namespace', 'Foo/Bar\\NewTests/');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeUnitTest',
            'module' => 'Article',
            '--type' => 'unit',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Tests/Unit/Baz/Bat/MyAwesomeUnitTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    // Type: Feature

    /** @test */
    public function it_generate_feature_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeFeatureTest',
            'module' => 'Article',
            '--type' => 'feature',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/Tests/Feature/MyAwesomeFeatureTest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_feature_test_file_with_content()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeFeatureTest',
            'module' => 'Article',
            '--type' => 'feature',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Tests/Feature/MyAwesomeFeatureTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_feature_test()
    {
        $this->app['config']->set('modules.generator.components.feature-test.path', 'Foo/Bar\\NewTests');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeFeatureTest',
            'module' => 'Article',
            '--type' => 'feature',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewTests/Baz/Bat/MyAwesomeFeatureTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_feature_test()
    {
        $this->app['config']->set('modules.generator.components.feature-test.namespace', 'Foo/Bar\\NewTests/');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeFeatureTest',
            'module' => 'Article',
            '--type' => 'feature',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Tests/Feature/Baz/Bat/MyAwesomeFeatureTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    // Type: CLI

    /** @test */
    public function it_generate_cli_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeCliTest',
            'module' => 'Article',
            '--type' => 'cli',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/CLI/Tests/MyAwesomeCliTest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_cli_test_file_with_content()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeCliTest',
            'module' => 'Article',
            '--type' => 'cli',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/CLI/Tests/MyAwesomeCliTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_cli_test()
    {
        $this->app['config']->set('modules.generator.components.cli-test.path', 'Foo/Bar\\NewTests');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeCliTest',
            'module' => 'Article',
            '--type' => 'cli',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewTests/Baz/Bat/MyAwesomeCliTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_cli_test()
    {
        $this->app['config']->set('modules.generator.components.cli-test.namespace', 'Foo/Bar\\NewTests/');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeCliTest',
            'module' => 'Article',
            '--type' => 'cli',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/CLI/Tests/Baz/Bat/MyAwesomeCliTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    // Type: API

    /** @test */
    public function it_generate_api_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeApiTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'plain',
            '--route' => 'some.api.url'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/Tests/MyAwesomeApiTest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_api_test_file_with_content()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeApiTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'plain',
            '--route' => 'some.api.url'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/MyAwesomeApiTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_api_test()
    {
        $this->app['config']->set('modules.generator.components.api-test.path', 'Foo/Bar\\NewTests');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'plain',
            '--route' => 'some.api.url'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewTests/Baz/Bat/MyAwesomeApiTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_api_test()
    {
        $this->app['config']->set('modules.generator.components.api-test.namespace', 'Foo/Bar\\NewTests/');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'plain',
            '--route' => 'some.api.url'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_create_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiCreateTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'create',
            '--route' => 'some.api.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiCreateTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_throws_exception_when_classes_not_provided_for_api_create_test_file()
    {
        $this->expectException(InvalidOptionException::class);

        $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiCreateTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'create',
            '--route' => 'some.api.url',
            '-n' => true,
        ]);
    }

    /** @test */
    public function it_can_generate_api_delete_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiDeleteTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'delete',
            '--route' => 'some.api.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiDeleteTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_list_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiListTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'list',
            '--route' => 'some.api.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiListTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_update_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiUpdateTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'update',
            '--route' => 'some.api.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiUpdateTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_view_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeApiViewTest',
            'module' => 'Article',
            '--type' => 'api',
            '--stub' => 'view',
            '--route' => 'some.api.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Tests/Baz/Bat/MyAwesomeApiViewTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    // Type: WEB

    /** @test */
    public function it_generate_web_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeWebTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'plain',
            '--route' => 'some.web.url',
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/WEB/Tests/MyAwesomeWebTest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_web_test_file_with_content()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'MyAwesomeWebTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'plain',
            '--route' => 'some.web.url',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Tests/MyAwesomeWebTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_web_test()
    {
        $this->app['config']->set('modules.generator.components.web-test.path', 'Foo/Bar\\NewTests');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'plain',
            '--route' => 'some.web.url',
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewTests/Baz/Bat/MyAwesomeWebTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_web_test()
    {
        $this->app['config']->set('modules.generator.components.web-test.namespace', 'Foo/Bar\\NewTests/');

        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'plain',
            '--route' => 'some.web.url',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Tests/Baz/Bat/MyAwesomeWebTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_web_create_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebCreateTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'create',
            '--route' => 'some.web.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Tests/Baz/Bat/MyAwesomeWebCreateTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_throws_exception_when_classes_not_provided_for_web_create_test_file()
    {
        $this->expectException(InvalidOptionException::class);

        $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebCreateTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'create',
            '--route' => 'some.web.url',
            '-n' => true,
        ]);
    }

    /** @test */
    public function it_can_generate_web_delete_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebDeleteTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'delete',
            '--route' => 'some.web.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Tests/Baz/Bat/MyAwesomeWebDeleteTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_web_update_test_file()
    {
        $code = $this->artisan('module:make:test', [
            'name' => 'Baz\\Bat/MyAwesomeWebUpdateTest',
            'module' => 'Article',
            '--type' => 'web',
            '--stub' => 'update',
            '--route' => 'some.web.url',
            '--model' => 'Some/Nested\\SomeTestModel',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Tests/Baz/Bat/MyAwesomeWebUpdateTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
