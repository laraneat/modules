<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\ModulesServiceProvider;
use Laraneat\Modules\Tests\TemporaryDirectory;

/*
 * Applications booted from their own bootstrap/app.php, the way "php artisan" and an HTTP worker
 * boot them: Testbench neither builds the framework caches nor loads the cached routes.
 */

/**
 * A new process of the application: nothing is kept from the previous one.
 */
function bootApplication(string $basePath): Application
{
    ModuleRepository::flushState();
    ServiceProvider::$optimizeCommands = ServiceProvider::$optimizeClearCommands = [];

    // With "opcache.enable_cli", this process has compiled the cache files of the previous one.
    foreach (function_exists('opcache_invalidate') ? glob($basePath.'/{bootstrap/cache,storage/readonly}/*.php', GLOB_BRACE) ?: [] : [] as $file) {
        @opcache_invalidate($file, true);
    }

    return require $basePath.'/bootstrap/app.php';
}

function runArtisan(string $basePath, string $command): void
{
    $kernel = bootApplication($basePath)->make(ConsoleKernel::class);

    expect($kernel->call($command))->toBe(0, $kernel->output());
}

function serve(string $basePath, string $uri): string
{
    $response = bootApplication($basePath)->make(HttpKernel::class)->handle(Request::create($uri));

    return $response->getStatusCode().' '.$response->getContent();
}

/**
 * Write a PHP file that the process may have included before.
 */
function writePhp(string $path, string $contents): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), recursive: true);
    }

    file_put_contents($path, $contents);

    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($path, true);
    }
}

/**
 * The next release adds a route file, a config file and a module, and bootstrap/cache is kept.
 * The cache files look old: OPcache does not check files written in the last two seconds.
 */
function release(string $basePath): void
{
    writePhp($basePath.'/modules/blog/routes/api/tags.php', "<?php Illuminate\Support\Facades\Route::get('tags', fn () => config('tags.title').' '.config('wiki.title'));");
    writePhp($basePath.'/modules/blog/config/tags.php', "<?php return ['title' => 'Tags'];");
    writePhp($basePath.'/modules/wiki/config/wiki.php', "<?php return ['title' => 'Wiki'];");
    file_put_contents($basePath.'/modules/wiki/composer.json', '{"name": "app/wiki", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}');

    foreach (glob($basePath.'/{bootstrap/cache,storage/framework}/*.php', GLOB_BRACE) ?: [] as $file) {
        touch($file, time() - 60);
    }

    clearstatcache();
}

/**
 * @return array{config: array<string, mixed>, routes: string, modules: array<string, mixed>}
 */
function caches(string $basePath, string $modules = 'bootstrap/cache/modules.php'): array
{
    return [
        'config' => eval('?>'.file_get_contents($basePath.'/bootstrap/cache/config.php')),
        'routes' => (string) file_get_contents($basePath.'/bootstrap/cache/routes-v7.php'),
        'modules' => eval('?>'.file_get_contents($basePath.'/'.$modules)),
    ];
}

beforeEach(function () {
    $this->basePath = TemporaryDirectory::create(__DIR__.'/../Fixtures/app');
    $this->workingDirectory = (string) getcwd();
    $this->environment = [];
    chdir($this->basePath);

    $this->application = function (string $routing = '->withRouting()'): void {
        $provider = ModulesServiceProvider::class;

        // "view:cache" compiles the views of the application.
        mkdir($this->basePath.'/resources/views', recursive: true);
        file_put_contents($this->basePath.'/.env', "APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=\nSESSION_DRIVER=array\nCACHE_STORE=array\n");
        file_put_contents($this->basePath.'/bootstrap/app.php', <<<PHP
            <?php

            \$GLOBALS['applicationsBooted'] = (\$GLOBALS['applicationsBooted'] ?? 0) + 1;

            return Illuminate\Foundation\Application::configure(basePath: dirname(__DIR__))
                ->withProviders([{$provider}::class])
                {$routing}
                ->withMiddleware()
                ->withExceptions()
                ->create();
            PHP);
    };

    $this->env = function (string $name, string $value): void {
        $this->environment[$name] = $_ENV[$name] ?? null;
        $_ENV[$name] = $_SERVER[$name] = $value;
        putenv($name.'='.$value);
    };
});

afterEach(function () {
    foreach ($this->environment as $name => $value) {
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);

        if ($value !== null) {
            $_ENV[$name] = $value;
            putenv($name.'='.$value);
        }
    }

    ModuleRepository::flushState();
    ServiceProvider::$optimizeCommands = ServiceProvider::$optimizeClearCommands = [];
    HandleExceptions::flushState($this);
    Facade::clearResolvedInstances();
    Container::setInstance(null);
    chdir($this->workingDirectory);
    unset($GLOBALS['cachedRoutesLoaded'], $GLOBALS['applicationsBooted']);

    // A test may have made the directory read-only.
    foreach (glob($this->basePath.'/storage/readonly{,/*}', GLOB_BRACE) ?: [] as $path) {
        chmod($path, 0755);
    }

    TemporaryDirectory::delete($this->basePath);
});

it('caches the config and the routes of the modules as they are on disk, not as the previous optimize saw them', function () {
    ($this->application)();

    runArtisan($this->basePath, 'optimize');

    expect(serve($this->basePath, '/api/posts'))->toBe('200 blog posts');

    release($this->basePath);
    runArtisan($this->basePath, 'optimize');

    $caches = caches($this->basePath);

    expect($caches['config'])->toMatchArray(['tags' => ['title' => 'Tags'], 'wiki' => ['title' => 'Wiki']])
        ->and($caches['routes'])->toContain('api/tags')
        ->and(array_keys($caches['modules']))->toBe(['blog', 'shop-order', 'wiki'])
        ->and($caches['modules']['blog']['config'])->toBe(['blog', 'tags'])
        ->and(serve($this->basePath, '/api/tags'))->toBe('200 Tags Wiki');
});

it('caches the routes for the route groups of the new config', function () {
    ($this->application)();
    runArtisan($this->basePath, 'optimize');

    // The process of the next optimize boots from the config cache of the previous release.
    writePhp($this->basePath.'/config/modules.php', <<<'PHP'
        <?php return ['routes' => [
            'api' => ['path' => 'routes/api', 'prefix' => 'api', 'middleware' => ['api'], 'except' => ['blog']],
            'web' => ['path' => 'routes/web', 'middleware' => ['web']],
            'admin' => ['path' => 'routes/admin', 'prefix' => 'admin', 'middleware' => ['web']],
        ]];
        PHP);
    writePhp($this->basePath.'/modules/shop-order/routes/admin/panel.php', "<?php Illuminate\Support\Facades\Route::get('panel', fn () => 'admin panel');");
    release($this->basePath);
    runArtisan($this->basePath, 'optimize');

    $caches = caches($this->basePath);

    expect($caches['routes'])->toContain('admin/panel')->not->toContain('api/posts')
        ->and($caches['modules']['blog']['routes'])->toHaveKeys(['web'])->not->toHaveKey('api')
        ->and($caches['modules']['shop-order']['routes'])->toHaveKey('admin')
        ->and(serve($this->basePath, '/admin/panel'))->toBe('200 admin panel')
        ->and(serve($this->basePath, '/api/posts'))->toStartWith('404');
})->skip(fn () => (bool) ini_get('opcache.enable_cli'), 'With "opcache.enable_cli", "route:cache" of Laravel itself reads the compiled config cache of the previous deploy.');

it('caches the config and the routes without writing the manifest cache', function () {
    ($this->env)('MODULES_CACHE', 'storage/readonly/modules.php');
    ($this->application)();
    runArtisan($this->basePath, 'optimize');

    release($this->basePath);
    $manifest = file_get_contents($this->basePath.'/storage/readonly/modules.php');
    chmod($this->basePath.'/storage/readonly/modules.php', 0444);
    chmod($this->basePath.'/storage/readonly', 0555);

    runArtisan($this->basePath, 'config:cache');
    runArtisan($this->basePath, 'route:cache');

    $caches = caches($this->basePath, 'storage/readonly/modules.php');

    expect($caches['config'])->toMatchArray(['tags' => ['title' => 'Tags'], 'wiki' => ['title' => 'Wiki']])
        ->and($caches['routes'])->toContain('api/tags')
        ->and(file_get_contents($this->basePath.'/storage/readonly/modules.php'))->toBe($manifest)
        ->and(serve($this->basePath, '/api/tags'))->toBe('200 Tags Wiki');
})->skip(fn () => DIRECTORY_SEPARATOR === '\\' || posix_getuid() === 0, 'File permissions are not enforced.');

it('caches the config and the routes as they are on disk when optimize is called outside of the console', function () {
    ($this->application)();
    runArtisan($this->basePath, 'optimize');
    release($this->basePath);

    // An HTTP request or an Octane worker: the package registers its commands only in the console,
    // so "module:cache" is not a part of this optimize and the manifest cache stays as it was.
    ($this->env)('APP_RUNNING_IN_CONSOLE', 'false');
    $app = bootApplication($this->basePath);
    $app->make(HttpKernel::class)->bootstrap();

    expect(Artisan::call('optimize'))->toBe(0, Artisan::output());

    $caches = caches($this->basePath);

    expect($app->runningInConsole())->toBeFalse()
        ->and($caches['config'])->toMatchArray(['tags' => ['title' => 'Tags'], 'wiki' => ['title' => 'Wiki']])
        ->and($caches['routes'])->toContain('api/tags')
        ->and(array_keys($caches['modules']))->toBe(['blog', 'shop-order']);
});

it('finds module routes by name in an application without a route service provider', function () {
    ($this->application)(routing: '');
    // The name and the action are set after the route is added, as in most route files.
    file_put_contents($this->basePath.'/modules/blog/routes/api/archive.php', "<?php Illuminate\Support\Facades\Route::get('archive', fn () => '')->uses('Modules\Blog\Http\ArchiveController@index');");

    $app = bootApplication($this->basePath);
    $app->make(HttpKernel::class)->bootstrap();
    $app->instance('request', Request::create('/'));

    expect($app['router']->has('blog.posts'))->toBeTrue()
        ->and($app['url']->route('blog.posts', absolute: false))->toBe('/api/posts')
        ->and($app['url']->action('Modules\Blog\Http\ArchiveController@index', absolute: false))->toBe('/api/archive');
});

it('loads the cached routes once, with or without a route service provider in the application', function (string $routing) {
    ($this->application)($routing);
    runArtisan($this->basePath, 'optimize');

    file_put_contents($this->basePath.'/bootstrap/cache/routes-v7.php', "\n\$GLOBALS['cachedRoutesLoaded'] = (\$GLOBALS['cachedRoutesLoaded'] ?? 0) + 1;\n", FILE_APPEND);

    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($this->basePath.'/bootstrap/cache/routes-v7.php', true);
    }

    expect(serve($this->basePath, '/api/posts'))->toBe('200 blog posts')
        ->and($GLOBALS['cachedRoutesLoaded'])->toBe(1)
        ->and(serve($this->basePath, '/blog'))->toBe("200 Blog: Welcome to the blog\n");
})->with([
    'without a route service provider' => [''],
    'with withRouting()' => ['->withRouting()'],
]);

it('caches the modules from a fresh application when the config is cached, and leaves the application of the process in place', function () {
    ($this->application)();
    runArtisan($this->basePath, 'optimize');
    release($this->basePath);

    $app = bootApplication($this->basePath);
    $kernel = $app->make(ConsoleKernel::class);
    $kernel->bootstrap();
    $modules = $app->make(ModuleRepository::class);
    $booted = $GLOBALS['applicationsBooted'];

    expect(array_keys($modules->all()))->toBe(['blog', 'shop-order'])
        ->and($kernel->call('module:cache'))->toBe(0, $kernel->output())
        ->and($GLOBALS['applicationsBooted'])->toBe($booted + 1)
        ->and(Container::getInstance())->toBe($app)
        ->and(Facade::getFacadeApplication())->toBe($app)
        // The provider of the fresh application has resolved the facade.
        ->and(Config::getFacadeRoot())->toBe($app->make('config'))
        ->and(array_keys($modules->all()))->toBe(['blog', 'shop-order', 'wiki']);
});

it('caches the modules from the application of the process when the config is not cached', function () {
    ($this->application)();

    $app = bootApplication($this->basePath);
    $kernel = $app->make(ConsoleKernel::class);
    $booted = $GLOBALS['applicationsBooted'];

    expect($kernel->call('module:cache'))->toBe(0, $kernel->output())
        ->and($GLOBALS['applicationsBooted'])->toBe($booted)
        ->and(file_exists($this->basePath.'/bootstrap/cache/modules.php'))->toBeTrue();
});
