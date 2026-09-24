<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Exceptions\InvalidConfiguration;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\ModulesServiceProvider;
use Modules\Blog\Models\Post;

it('is registered by package discovery with the Modules alias', function () {
    $composerJson = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composerJson['extra']['laravel'])->toBe([
        'providers' => [ModulesServiceProvider::class],
        'aliases' => ['Modules' => Modules::class],
    ])
        ->and(app()->getProviders(ModulesServiceProvider::class))->toHaveCount(1)
        ->and(\Modules::get('blog')->package)->toBe('app/blog');
});

it('merges its config with the config of the application', function () {
    expect(config('modules'))->toBe([
        'path' => base_path('modules'),
        'namespace' => 'Modules',
        'vendor' => 'app',
        'routes' => [
            'api' => ['path' => 'routes/api', 'prefix' => 'api', 'middleware' => ['api']],
            'web' => ['path' => 'routes/web', 'middleware' => ['web']],
        ],
        'generators' => [],
    ]);

    $this->files(['config/modules.php' => "<?php return ['vendor' => 'acme'];"]);
    $this->reboot();

    expect(config('modules.vendor'))->toBe('acme')
        ->and(config('modules.namespace'))->toBe('Modules');
});

it('publishes the config and the module template', function () {
    $published = static fn (string $tag): array => collect(ServiceProvider::pathsToPublish(ModulesServiceProvider::class, $tag))
        ->mapWithKeys(static fn (string $to, string $from): array => [realpath($from) => $to])
        ->all();

    expect($published('modules-config'))
        ->toBe([realpath(__DIR__.'/../../config/modules.php') => config_path('modules.php')])
        ->and($published('modules-stubs'))
        ->toBe([realpath(__DIR__.'/../../resources/stubs/module/default') => base_path('stubs/module/default')]);

    $this->artisan('vendor:publish --tag=modules-stubs')->assertSuccessful();

    expect(file_exists($this->path('stubs/module/default/composer.json.stub')))->toBeTrue();
});

it('adds its cache to optimize and optimize:clear', function () {
    // The framework caches are skipped: they would boot the application from bootstrap/app.php.
    $this->artisan('optimize --except=config,events,routes,views')
        ->expectsOutputToContain('modules')
        ->assertSuccessful();

    expect(file_exists($this->path('bootstrap/cache/modules.php')))->toBeTrue();

    $this->artisan('optimize:clear --except=config,cache,compiled,events,routes,views')
        ->expectsOutputToContain('modules')
        ->assertSuccessful();

    expect(file_exists($this->path('bootstrap/cache/modules.php')))->toBeFalse();
});

it('shows the modules in the about command', function () {
    $about = static function (): array {
        Artisan::call('about', ['--only' => 'modules', '--json' => true]);

        return json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    };

    expect($about())->toBe(['modules' => ['modules' => 2, 'path' => 'modules', 'cache' => false]]);

    $this->artisan('module:cache');

    expect($about())->toBe(['modules' => ['modules' => 2, 'path' => 'modules', 'cache' => true]]);
});

it('keeps the HTTP runtime free of console conventions', function () {
    $this->rebootForHttp();

    expect(app()->runningInConsole())->toBeFalse()
        ->and(Factory::resolveFactoryName(Post::class))->toBe('Database\\Factories\\Modules\\Blog\\Models\\PostFactory')
        ->and(config('tinker.alias'))->toBeNull()
        ->and(app('migrator')->paths())->toBe([])
        ->and(Artisan::all())->not->toHaveKeys(['blog:publish', 'module:make'])
        ->and(Artisan::all()['make:model']->getDefinition()->hasOption('module'))->toBeFalse();

    $this->get('/api/posts')->assertOk()->assertSee('blog posts');
});

it('refuses an invalid route group', function (mixed $group) {
    $this->files(['config/modules.php' => '<?php return ["routes" => ["api" => '.var_export($group, true).']];']);

    expect(fn () => $this->reboot())->toThrow(InvalidConfiguration::class, 'Invalid config/modules.php: the [api] route group must have a "path".');
})->with([
    'no path' => [['prefix' => 'api']],
    'path is not a string' => [['path' => ['routes/api']]],
    'not an array' => ['routes/api'],
]);

it('refuses a generator namespace that is not a string', function () {
    $this->files(['config/modules.php' => '<?php return ["generators" => ["make:controller" => ["Http"]]];']);

    expect(fn () => $this->reboot())->toThrow(InvalidConfiguration::class, 'the namespace of the [make:controller] generator must be a string.');
});

it('refuses an empty make:command namespace', function (string $namespace) {
    $this->files(['config/modules.php' => '<?php return ["generators" => ["make:command" => '.var_export($namespace, true).']];']);

    expect(fn () => $this->reboot())->toThrow(InvalidConfiguration::class, 'the namespace of the [make:command] generator can not be empty');
})->with(['', '\\']);

it('resolves a relative modules path against the application', function () {
    $this->files(['config/modules.php' => '<?php return ["path" => "modules"];']);
    chdir(sys_get_temp_dir());
    $this->reboot();

    expect(array_keys(Modules::all()))->toBe(['blog', 'shop-order'])
        ->and(Modules::get('blog')->path)->toBe($this->path('modules/blog'));
});

it('lets module providers that boot first use the module translations, views and components', function () {
    $this->providersBeforeModules = [ModuleProviderBootingFirst::class];
    $this->reboot();

    expect(ModuleProviderBootingFirst::$seen)->toBe([
        'Welcome to the blog',
        true,
        'Modules\\Blog\\View\\Components',
    ]);
});

it('stores the manifest cache where MODULES_CACHE points', function (string $path, string $expected) {
    $_ENV['MODULES_CACHE'] = $path;
    $this->reboot();
    // Registered after the reboot, which destroys the previous application.
    $this->beforeApplicationDestroyed(static function (): void {
        unset($_ENV['MODULES_CACHE']);
    });

    $this->artisan('module:cache')->assertSuccessful();

    expect(app(ModuleRepository::class)->cachePath())->toBe($expected)
        ->and(file_exists($expected))->toBeTrue();
})->with([
    'relative' => ['storage/modules.php', fn () => base_path('storage/modules.php')],
    'absolute' => [fn () => $this->path('storage/framework/modules.php'), fn () => $this->path('storage/framework/modules.php')],
]);

final class ModuleProviderBootingFirst extends ServiceProvider
{
    /**
     * @var list<mixed>
     */
    public static array $seen = [];

    public function boot(): void
    {
        self::$seen = [
            __('blog::messages.welcome'),
            view()->exists('blog::index'),
            Blade::getClassComponentNamespaces()['blog'] ?? null,
        ];
    }
}
