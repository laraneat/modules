<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RouteObject;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Registrars\RouteRegistrar;

function moduleRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->reject(static fn (RouteObject $route): bool => str_starts_with($route->uri(), 'storage/'))
        ->mapWithKeys(static fn (RouteObject $route): array => [$route->uri() => $route->gatherMiddleware()])
        ->all();
}

it('loads the route files into the route groups', function () {
    expect(moduleRoutes())->toBe([
        'api/posts' => ['api'],
        'api/v1/feed' => ['api'],
        'api/v1/admin/stats' => ['api'],
        'blog' => ['web'],
        'orders' => ['web'],
    ]);

    $this->get('/api/v1/admin/stats')->assertOk()->assertSee('blog stats');
    $this->get('/blog')->assertOk()->assertSee('Blog: Welcome to the blog');
    $this->get('/orders')->assertOk();
    expect(route('blog.posts', absolute: false))->toBe('/api/posts');
});

it('applies every attribute of a route group', function () {
    $this->files(['config/modules.php' => <<<'PHP'
        <?php return ['routes' => [
            'api' => ['path' => 'routes/api', 'prefix' => '/api/', 'as' => 'api.', 'middleware' => ['api', 'auth:sanctum']],
            'web' => ['path' => 'routes/web', 'domain' => 'shop.test'],
        ]];
        PHP]);
    $this->reboot();

    // Routes with a domain are listed first.
    expect(moduleRoutes())->toBe([
        'blog' => [],
        'orders' => [],
        'api/posts' => ['api', 'auth:sanctum'],
        'api/v1/feed' => ['api', 'auth:sanctum'],
        'api/v1/admin/stats' => ['api', 'auth:sanctum'],
    ])
        ->and(Route::getRoutes()->getByName('api.blog.posts')?->uri())->toBe('api/posts')
        ->and(Route::getRoutes()->getByName('shop-order.orders')?->getDomain())->toBe('shop.test');
});

it('uses the directory as the prefix of a group without a prefix', function () {
    $this->files(['config/modules.php' => '<?php return ["routes" => ["api" => ["path" => "routes/api"]]];']);
    $this->reboot();

    expect(array_keys(moduleRoutes()))->toBe(['posts', 'v1/feed', 'v1/admin/stats']);
});

it('does not load the route files when the routes are cached', function () {
    // Laravel loads the cached routes after the providers boot, Testbench does not load them at all.
    $this->files(['bootstrap/cache/routes-v7.php' => '<?php']);
    $this->reboot();

    expect(app()->routesAreCached())->toBeTrue()
        ->and(moduleRoutes())->toBe([]);
});

it('skips route groups that are not arrays', function () {
    // Route files register their routes through the facade.
    app()->instance('router', $router = new Router(app('events'), app()));
    Route::clearResolvedInstance('router');
    $groups = ['api' => 'routes/api', 'web' => ['path' => 'routes/web']];

    (new RouteRegistrar($router, $groups, app(ModuleRepository::class)->manifest()))->register();

    expect(array_map(static fn (RouteObject $route): string => $route->uri(), $router->getRoutes()->getRoutes()))->toBe(['blog', 'orders']);
});
