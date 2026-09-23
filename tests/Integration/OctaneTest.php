<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\ModulesServiceProvider;
use Laraneat\Modules\Tests\TemporaryDirectory;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\OctaneResponse;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Worker;

/*
 * Real Octane workers (the application sandbox is cloned and flushed per request) serving
 * module routes in this process, the way they are served under Swoole or RoadRunner.
 */

const MODULE_URIS = ['/api/posts', '/api/v1/feed', '/api/v1/admin/stats', '/blog', '/orders'];

/**
 * Keeps no reference to the responses, so they do not count as used memory.
 */
final class StatusClient implements Client
{
    /** @var array<int, int> */
    public array $statuses = [];

    /** @var array<string, string> */
    public array $contents = [];

    /** @var list<string> */
    public array $errors = [];

    public function marshalRequest(RequestContext $context): array
    {
        return [$context->request, $context];
    }

    public function respond(RequestContext $context, OctaneResponse $response): void
    {
        $status = $response->response->getStatusCode();
        $this->statuses[$status] = ($this->statuses[$status] ?? 0) + 1;
        $this->contents[$context->request->getPathInfo()] = (string) $response->response->getContent();
    }

    public function error(Throwable $e, Application $app, Request $request, RequestContext $context): void
    {
        $this->errors[] = $e->getMessage();
    }
}

/**
 * An application in a temporary directory, with or without the modules provider. Without it,
 * the application serves the same routes from its own route files.
 */
function octaneApplication(bool $modules): string
{
    $basePath = TemporaryDirectory::create(__DIR__.'/../Fixtures/app');
    $providers = $modules ? '[Laravel\Octane\OctaneServiceProvider::class, '.ModulesServiceProvider::class.'::class]' : '[Laravel\Octane\OctaneServiceProvider::class]';
    $routes = $modules ? '' : <<<'PHP'
        ->withRouting(function () {
            Illuminate\Support\Facades\Route::middleware('api')->prefix('api')->group(function () {
                Illuminate\Support\Facades\Route::get('posts', fn () => 'blog posts');
                Illuminate\Support\Facades\Route::get('v1/feed', fn () => 'blog feed v1');
                Illuminate\Support\Facades\Route::get('v1/admin/stats', fn () => 'blog stats');
            });
            Illuminate\Support\Facades\Route::middleware('web')->group(function () {
                Illuminate\Support\Facades\Route::get('blog', fn () => "Blog: Welcome to the blog\n");
                Illuminate\Support\Facades\Route::get('orders', fn () => 'orders');
            });
        })
        PHP;

    file_put_contents($basePath.'/.env', "APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=\nSESSION_DRIVER=array\nCACHE_STORE=array\n");
    file_put_contents($basePath.'/bootstrap/app.php', <<<PHP
        <?php

        return Illuminate\Foundation\Application::configure(basePath: dirname(__DIR__))
            ->withProviders({$providers})
            {$routes}
            ->withMiddleware()
            ->withExceptions()
            ->create();
        PHP);

    return $basePath;
}

function octaneRequest(Worker $worker, string $uri): void
{
    $request = Request::create($uri);

    $worker->handle($request, new RequestContext(['request' => $request]));
}

/**
 * Memory kept by the worker after serving the requests.
 *
 * @param  list<string>  $uris
 */
function retainedMemory(Worker $worker, array $uris, int $requests): int
{
    gc_collect_cycles();
    $before = memory_get_usage();

    for ($i = 0; $i < $requests; $i++) {
        octaneRequest($worker, $uris[$i % count($uris)]);
    }

    gc_collect_cycles();

    return memory_get_usage() - $before;
}

beforeEach(function () {
    // What vendor/laravel/octane/bin/bootstrap.php does.
    $_ENV['APP_RUNNING_IN_CONSOLE'] = $_SERVER['APP_RUNNING_IN_CONSOLE'] = 'false';
    ModuleRepository::flushState();
    $this->basePaths = [$workingDirectory = TemporaryDirectory::create()];
    $this->workingDirectory = (string) getcwd();
    chdir($workingDirectory);
});

afterEach(function () {
    unset($_ENV['APP_RUNNING_IN_CONSOLE'], $_SERVER['APP_RUNNING_IN_CONSOLE']);
    ModuleRepository::flushState();
    HandleExceptions::flushState($this);
    Facade::clearResolvedInstances();
    Container::setInstance(null);
    chdir($this->workingDirectory);
    array_map(TemporaryDirectory::delete(...), $this->basePaths);
});

it('serves module routes with the state of the first request', function () {
    $client = new StatusClient;
    $worker = new Worker(new ApplicationFactory($this->basePaths[] = octaneApplication(modules: true)), $client);
    $worker->boot();

    $snapshots = [];
    $worker->onRequestHandled(static function (Request $request, $response, Application $sandbox) use (&$snapshots): void {
        $snapshots[] = [
            count($sandbox['router']->getRoutes()),
            $sandbox['view']->getFinder()->getHints(),
            $sandbox['translation.loader']->namespaces(),
            $sandbox['translation.loader']->jsonPaths(),
            $sandbox['config']->get('blog'),
            $sandbox['config']->get('tinker.alias'),
            $sandbox->runningInConsole(),
        ];
    });

    for ($i = 0; $i < 500; $i++) {
        octaneRequest($worker, MODULE_URIS[$i % count(MODULE_URIS)]);
    }

    expect($client->errors)->toBe([])
        ->and($client->statuses)->toBe([200 => 500])
        ->and($client->contents)->toBe([
            '/api/posts' => 'blog posts',
            '/api/v1/feed' => 'blog feed v1',
            '/api/v1/admin/stats' => 'blog stats',
            '/blog' => "Blog: Welcome to the blog\n",
            '/orders' => 'orders',
        ])
        ->and(array_unique($snapshots, SORT_REGULAR))->toHaveCount(1)
        ->and($snapshots[0][1])->toMatchArray(['blog' => [$this->basePaths[1].'/modules/blog/resources/views']])
        ->and($snapshots[0][3])->toBe([$this->basePaths[1].'/modules/blog/lang'])
        ->and($snapshots[0][4])->toBe(['title' => 'Blog', 'per_page' => 15])
        ->and($snapshots[0][5])->toBeNull()
        ->and($snapshots[0][6])->toBeFalse();

    $worker->terminate();
});

it('keeps no more memory than the same routes of the application', function () {
    $application = new Worker(new ApplicationFactory($this->basePaths[] = octaneApplication(modules: false)), $applicationClient = new StatusClient);
    $application->boot();
    $modules = new Worker(new ApplicationFactory($this->basePaths[] = octaneApplication(modules: true)), $modulesClient = new StatusClient);
    $modules->boot();

    // Laravel and Octane themselves keep some memory per request, so the workers are compared.
    retainedMemory($application, MODULE_URIS, 200);
    retainedMemory($modules, MODULE_URIS, 200);
    $retained = ['application' => 0, 'modules' => 0];

    for ($round = 0; $round < 3; $round++) {
        $retained['application'] += retainedMemory($application, MODULE_URIS, 500);
        $retained['modules'] += retainedMemory($modules, MODULE_URIS, 500);
    }

    expect($applicationClient->statuses)->toBe([200 => 1700])
        ->and($modulesClient->statuses)->toBe([200 => 1700])
        ->and($modulesClient->contents)->toBe($applicationClient->contents)
        ->and($retained['modules'])->toBeLessThan($retained['application'] + 32 * 1024);

    $application->terminate();
    $modules->terminate();
});

it('boots the application again in the same process', function () {
    $factory = new ApplicationFactory($this->basePaths[] = octaneApplication(modules: true));
    $routes = static fn (Application $app): array => array_map(
        static fn ($route): string => implode('|', $route->methods()).' '.$route->uri(),
        $app['router']->getRoutes()->getRoutes(),
    );

    $first = $factory->createApplication();
    $second = $factory->createApplication();

    expect($routes($second))->toBe($routes($first))
        ->and($routes($first))->toContain('GET|HEAD api/posts', 'GET|HEAD blog', 'GET|HEAD orders')
        ->and($routes($first))->toBe(array_values(array_unique($routes($first))))
        ->and($second['config']->get('blog'))->toBe($first['config']->get('blog'));
});
