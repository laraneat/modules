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
class RouteMakeCommandTest extends BaseTestCase
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
    public function it_generates_route_file()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_route',
            'module' => 'Article',
            '--url' => 'some/route',
            '-n' => true
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/Routes/nested/some_route.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_route_file_with_content()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_route',
            'module' => 'Article',
            '--url' => 'some/route',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_route_file()
    {
        $this->app['config']->set('modules.generator.components.api-route.path', 'Foo/Bar\\Routes');

        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_route',
            'module' => 'Article',
            '--url' => 'some/route',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/Routes/nested/some_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_recognized_view_route()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/v1/view_posts',
            'module' => 'Article',
            '--url' => 'posts/{post}',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/v1/view_posts.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_get_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_get_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/get/route',
            '--method' => 'get',
            '--name' => 'api.nested.some_get_route',
            '--action' => 'SomeGetAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_get_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_post_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_post_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/post/route',
            '--method' => 'post',
            '--name' => 'api.nested.some_post_route',
            '--action' => 'SomePostAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_post_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_put_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_put_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/put/route',
            '--method' => 'put',
            '--name' => 'api.nested.some_put_route',
            '--action' => 'SomePutAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_put_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_patch_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_patch_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/patch/route',
            '--method' => 'patch',
            '--name' => 'api.nested.some_patch_route',
            '--action' => 'SomePatchAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_patch_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_delete_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_delete_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/delete/route',
            '--method' => 'delete',
            '--name' => 'api.nested.some_delete_route',
            '--action' => 'SomeDeleteAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_delete_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_api_route_with_options_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_options_route',
            'module' => 'Article',
            '--ui' => 'api',
            '--url' => 'some/options/route',
            '--method' => 'options',
            '--name' => 'api.nested.some_options_route',
            '--action' => 'SomeOptionsAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/Routes/nested/some_options_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_get_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_get_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/get/route',
            '--method' => 'get',
            '--name' => 'web.nested.some_get_route',
            '--action' => 'SomeGetAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_get_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_post_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_post_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/post/route',
            '--method' => 'post',
            '--name' => 'web.nested.some_post_route',
            '--action' => 'SomePostAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_post_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_put_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_put_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/put/route',
            '--method' => 'put',
            '--name' => 'web.nested.some_put_route',
            '--action' => 'SomePutAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_put_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_patch_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_patch_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/patch/route',
            '--method' => 'patch',
            '--name' => 'web.nested.some_patch_route',
            '--action' => 'SomePatchAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_patch_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_delete_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_delete_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/delete/route',
            '--method' => 'delete',
            '--name' => 'web.nested.some_delete_route',
            '--action' => 'SomeDeleteAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_delete_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_web_route_with_options_method()
    {
        $code = $this->artisan('module:make:route', [
            'name' => '/nested/some_options_route',
            'module' => 'Article',
            '--ui' => 'web',
            '--url' => 'some/options/route',
            '--method' => 'options',
            '--name' => 'web.nested.some_options_route',
            '--action' => 'SomeOptionsAction',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/WEB/Routes/nested/some_options_route.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
