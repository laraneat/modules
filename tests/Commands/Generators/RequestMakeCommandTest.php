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
class RequestMakeCommandTest extends BaseTestCase
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
    public function it_generates_api_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'MyAwesomeApiRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/Requests/MyAwesomeApiRequest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_api_request_file_with_content()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'MyAwesomeApiRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/MyAwesomeApiRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_api_request()
    {
        $this->app['config']->set('modules.generator.components.api-request.path', 'Foo/Bar\\NewRequests');

        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewRequests/Baz/Bat/MyAwesomeApiRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_api_request()
    {
        $this->app['config']->set('modules.generator.components.api-request.namespace', 'Foo/Bar\\NewRequests/');

        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_create_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiCreateRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'create',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiCreateRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_throws_exception_when_classes_not_provided_for_api_create_request_file()
    {
        $this->expectException(InvalidOptionException::class);

        $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiCreateRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'create',
            '-n' => '',
        ]);
    }

    /** @test */
    public function it_can_generate_api_delete_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiDeleteRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'delete',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiDeleteRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_list_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiListRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'list',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiListRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_update_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiUpdateRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'update',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiUpdateRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_api_view_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeApiViewRequest',
            'module' => 'Article',
            '--ui' => 'api',
            '--stub' => 'view',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Requests/Baz/Bat/MyAwesomeApiViewRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_web_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'MyAwesomeWebRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'plain'
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/WEB/Requests/MyAwesomeWebRequest.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_web_request_file_with_content()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'MyAwesomeWebRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Requests/MyAwesomeWebRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_web_request()
    {
        $this->app['config']->set('modules.generator.components.web-request.path', 'Foo/Bar\\NewRequests');

        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/NewRequests/Baz/Bat/MyAwesomeWebRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_web_request()
    {
        $this->app['config']->set('modules.generator.components.web-request.namespace', 'Foo/Bar\\NewRequests/');

        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'plain'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Requests/Baz/Bat/MyAwesomeWebRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_web_create_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebCreateRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'create',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Requests/Baz/Bat/MyAwesomeWebCreateRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_throws_exception_when_classes_not_provided_for_web_create_request_file()
    {
        $this->expectException(InvalidOptionException::class);

        $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebCreateRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'create',
            '-n' => '',
        ]);
    }

    /** @test */
    public function it_can_generate_web_delete_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebDeleteRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'delete',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Requests/Baz/Bat/MyAwesomeWebDeleteRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_generate_web_update_request_file()
    {
        $code = $this->artisan('module:make:request', [
            'name' => 'Baz\\Bat/MyAwesomeWebUpdateRequest',
            'module' => 'Article',
            '--ui' => 'web',
            '--stub' => 'update',
            '--model' => 'Some/Nested\\Model',
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Requests/Baz/Bat/MyAwesomeWebUpdateRequest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
