<?php

declare(strict_types=1);

use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\Exceptions\ModulesException;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModuleRepository;
use Modules\Blog\Database\Seeders\BlogSeeder;
use Modules\Blog\Database\Seeders\CategorySeeder_10;
use Modules\Blog\Database\Seeders\Deployment\DemoSeeder_1;
use Modules\Blog\Database\Seeders\PostSeeder_2;
use Modules\ShopOrder\Database\Seeders\Deployment\ShipmentSeeder_1;
use Modules\ShopOrder\Database\Seeders\OrderSeeder_1;

it('lists the modules by directory name', function () {
    expect(Modules::all())->toEqual([
        'blog' => new Module(
            name: 'blog',
            package: 'app/blog',
            namespace: 'Modules\\Blog',
            path: $this->path('modules/blog'),
            sourcePath: $this->path('modules/blog/src'),
        ),
        'shop-order' => new Module(
            name: 'shop-order',
            package: 'app/shop-order',
            namespace: 'Modules\\ShopOrder',
            path: $this->path('modules/shop-order'),
            sourcePath: $this->path('modules/shop-order/src'),
        ),
    ]);
});

it('finds a module by name', function () {
    expect(Modules::find('shop-order')?->package)->toBe('app/shop-order')
        ->and(Modules::get('blog')->namespace)->toBe('Modules\\Blog')
        ->and(Modules::find('notes'))->toBeNull()
        ->and(Modules::find('Blog'))->toBeNull();
});

it('fails to get a missing module', function () {
    expect(fn () => Modules::get('notes'))
        ->toThrow(ModuleNotFound::class, 'Module [notes] not found.')
        ->and(ModuleNotFound::named('notes'))->toBeInstanceOf(ModulesException::class);
});

it('uses the module root as the source path when the root namespace maps to it', function () {
    $this->files(['modules/flat/composer.json' => json_encode([
        'name' => 'app/flat',
        'autoload' => ['psr-4' => ['Modules\\Flat\\' => '']],
    ])]);
    $this->reboot();

    expect(Modules::get('flat')->sourcePath)->toBe($this->path('modules/flat'));
});

it('orders seeders by the number at the end of their class name', function () {
    expect(Modules::seeders())->toBe([
        OrderSeeder_1::class,
        PostSeeder_2::class,
        CategorySeeder_10::class,
        BlogSeeder::class,
    ]);
});

it('collects seeders of the given subdirectories', function (array $directories) {
    expect(Modules::seeders(...$directories))->toBe([
        DemoSeeder_1::class,
        ShipmentSeeder_1::class,
        OrderSeeder_1::class,
        PostSeeder_2::class,
        CategorySeeder_10::class,
        BlogSeeder::class,
    ]);
})->with([
    'root first' => [['', 'Deployment']],
    'root last' => [['Deployment', '']],
    'slashes and duplicates' => [['/Deployment/', 'Deployment', '']],
]);

it('collects only the seeders of a subdirectory', function () {
    expect(Modules::seeders('Deployment'))->toBe([DemoSeeder_1::class, ShipmentSeeder_1::class])
        ->and(Modules::seeders('/Deployment\\'))->toBe([DemoSeeder_1::class, ShipmentSeeder_1::class])
        ->and(Modules::seeders('Missing'))->toBe([]);
});

it('builds the manifest once per process', function () {
    $manifest = app(ModuleRepository::class)->manifest();

    $this->files(['modules/wiki/composer.json' => json_encode([
        'name' => 'app/wiki',
        'autoload' => ['psr-4' => ['Modules\\Wiki\\' => 'src/']],
    ])]);
    $this->reloadApplication();

    expect(app(ModuleRepository::class)->manifest())->toBe($manifest)
        ->and(Modules::find('wiki'))->toBeNull();

    app(ModuleRepository::class)->refresh();

    expect(Modules::find('wiki'))->not->toBeNull();
});

it('keeps separate manifests for separate settings', function () {
    app(ModuleRepository::class)->manifest();

    $this->files([
        'config/modules.php' => '<?php return ["path" => base_path("packages")];',
        'packages/wiki/composer.json' => json_encode([
            'name' => 'app/wiki',
            'autoload' => ['psr-4' => ['Modules\\Wiki\\' => 'src/']],
        ]),
    ]);
    $this->reloadApplication();

    expect(array_keys(Modules::all()))->toBe(['wiki']);
});

it('reads the manifest from the cache', function () {
    $modules = app(ModuleRepository::class);
    $modules->cache();

    $this->files(['modules/wiki/composer.json' => json_encode([
        'name' => 'app/wiki',
        'autoload' => ['psr-4' => ['Modules\\Wiki\\' => 'src/']],
    ])]);
    $this->reboot();
    $modules = app(ModuleRepository::class);

    expect($modules->isCached())->toBeTrue()
        ->and($modules->isCacheStale())->toBeTrue()
        ->and(array_keys($modules->all()))->toBe(['blog', 'shop-order']);

    $modules->refresh();

    expect(array_keys($modules->all()))->toBe(['blog', 'shop-order', 'wiki'])
        ->and($modules->isCacheStale())->toBeFalse();

    $modules->clearCache();

    expect($modules->isCached())->toBeFalse()
        ->and($modules->isCacheStale())->toBeFalse()
        ->and(array_keys($modules->all()))->toBe(['blog', 'shop-order', 'wiki']);
});

it('does not create the cache on refresh', function () {
    $modules = app(ModuleRepository::class);
    $modules->refresh();

    expect($modules->isCached())->toBeFalse();
});
