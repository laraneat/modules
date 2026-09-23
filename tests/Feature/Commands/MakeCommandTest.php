<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\ModuleRepository;

beforeEach(function () {
    $this->composerJson = file_get_contents($this->path('composer.json'));
});

it('creates a module and installs it with Composer', function () {
    $this->expectComposer(['update', '--', 'app/wiki-pages'], during: function () {
        // Composer reads the module and the updated composer.json.
        expect($this->readJson('composer.json')['require'])->toHaveKey('app/wiki-pages')
            ->and(file_exists($this->path('modules/wiki-pages/composer.json')))->toBeTrue();
    });

    $this->artisan('module:make', ['name' => 'WikiPages'])
        ->expectsOutputToContain('Module [wiki-pages] created in [modules/wiki-pages].')
        ->assertSuccessful();

    Process::assertRanTimes(fn () => true, 1);

    expect($this->readJson('modules/wiki-pages/composer.json'))->toBe([
        'name' => 'app/wiki-pages',
        'description' => 'The wiki-pages module.',
        'type' => 'library',
        'autoload' => ['psr-4' => [
            'Modules\\WikiPages\\' => 'src/',
            'Modules\\WikiPages\\Database\\Factories\\' => 'database/factories/',
            'Modules\\WikiPages\\Database\\Seeders\\' => 'database/seeders/',
        ]],
        'autoload-dev' => ['psr-4' => ['Modules\\WikiPages\\Tests\\' => 'tests/']],
    ])
        ->and(collect(File::allFiles($this->path('modules/wiki-pages'), true))
            ->map(fn (SplFileInfo $file): string => $file->getRelativePathname())->sort()->values()->all())
        ->toBe([
            'composer.json',
            'database/factories/.gitkeep',
            'database/migrations/.gitkeep',
            'database/seeders/.gitkeep',
            'src/Models/.gitkeep',
            'tests/.gitkeep',
        ])
        ->and($this->readJson('composer.json'))->toBe([
            'name' => 'laraneat/fixture-app',
            'type' => 'project',
            'require' => [
                'php' => '^8.3',
                'app/wiki-pages' => '*@dev',
                'laravel/framework' => '^13.0',
            ],
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
            'config' => ['sort-packages' => true],
            'minimum-stability' => 'stable',
            'repositories' => [
                ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
                ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*']],
                ['packagist.org' => false],
            ],
            'autoload-dev' => ['psr-4' => ['Modules\\WikiPages\\Tests\\' => 'modules/wiki-pages/tests/']],
        ])
        ->and(Modules::get('wiki-pages')->namespace)->toBe('Modules\\WikiPages');
});

it('only edits composer.json with --no-update', function () {
    $this->artisan('module:make wiki --no-update')
        ->expectsOutputToContain('Install it with: composer update app/wiki')
        ->assertSuccessful();

    Process::assertNothingRan();

    expect($this->readJson('composer.json')['require'])->toHaveKey('app/wiki');
});

it('reports a Composer failure and keeps the module', function () {
    $this->expectComposer(['update', '--', 'app/wiki'], exitCode: 2);

    $this->artisan('module:make wiki')
        ->expectsOutputToContain('Composer failed to install the module. Fix the problem and run: composer update app/wiki')
        ->assertFailed();

    expect(is_dir($this->path('modules/wiki')))->toBeTrue()
        ->and($this->readJson('composer.json')['require'])->toHaveKey('app/wiki');
});

it('uses the vendor and the namespace of the config', function () {
    $this->files(['config/modules.php' => '<?php return ["vendor" => "acme", "namespace" => "Acme\\\\Domain"];']);
    $this->reboot();

    $this->artisan('module:make billing --no-update')->assertSuccessful();

    expect($this->readJson('modules/billing/composer.json'))
        ->name->toBe('acme/billing')
        ->autoload->toBe(['psr-4' => [
            'Acme\\Domain\\Billing\\' => 'src/',
            'Acme\\Domain\\Billing\\Database\\Factories\\' => 'database/factories/',
            'Acme\\Domain\\Billing\\Database\\Seeders\\' => 'database/seeders/',
        ]])
        ->and($this->readJson('composer.json')['repositories'][1]['exclude'])->toBe(['acme/*']);
});

it('updates the module cache when it exists', function () {
    $this->artisan('module:cache');

    $this->artisan('module:make wiki --no-update')->assertSuccessful();

    expect(array_keys(require $this->path('bootstrap/cache/modules.php')))->toBe(['blog', 'shop-order', 'wiki']);
});

it('renders a preset of the application', function () {
    Date::setTestNow('2026-09-23 10:20:30');
    $this->files([
        'stubs/module/api/composer.json.stub' => <<<'JSON'
            {
                "name": "{{ package }}",
                "autoload": {"psr-4": {"{{ namespace|json }}\\": "src/"}},
                "extra": {"module": "{{ name|studly }}", "vendor": "{{ vendor }}"}
            }
            JSON,
        'stubs/module/api/src/Models/{{ name|studly|singular }}.php.stub' => "<?php\n\nnamespace {{ namespace }}\\Models;\n\nfinal class {{ name|studly|singular }} {}\n",
        'stubs/module/api/database/migrations/{{ date }}_create_{{ name|snake }}_table.php.stub' => '<?php // {{ name|snake|plural }}',
        'stubs/module/api/README.md' => '# {{ name }} stays as is',
    ]);

    $this->artisan('module:make shop-items --preset=api --no-update')->assertSuccessful();

    expect(file_get_contents($this->path('modules/shop-items/src/Models/ShopItem.php')))
        ->toBe("<?php\n\nnamespace Modules\\ShopItems\\Models;\n\nfinal class ShopItem {}\n")
        ->and(file_get_contents($this->path('modules/shop-items/database/migrations/2026_09_23_102030_create_shop_items_table.php')))
        ->toBe('<?php // shop_items')
        ->and(file_get_contents($this->path('modules/shop-items/README.md')))->toBe('# {{ name }} stays as is')
        ->and($this->readJson('modules/shop-items/composer.json')['extra'])->toBe(['module' => 'ShopItems', 'vendor' => 'app']);
});

it('prefers the published default template', function () {
    $this->files([
        'stubs/module/default/composer.json.stub' => '{"name": "{{ package }}", "autoload": {"psr-4": {"{{ namespace|json }}\\\\": "lib/"}}}',
    ]);

    $this->artisan('module:make wiki --no-update')->assertSuccessful();

    expect(Modules::get('wiki')->sourcePath)->toBe($this->path('modules/wiki/lib'));
});

it('refuses invalid input without touching anything', function (string $command, string $error) {
    $this->files(['stubs/module/broken/src/.gitkeep' => '']);

    $this->artisan($command)->expectsOutputToContain($error)->assertFailed();

    Process::assertNothingRan();

    expect(file_get_contents($this->path('composer.json')))->toBe($this->composerJson)
        ->and(array_keys(app(ModuleRepository::class)->manifest()))->toBe(['blog', 'shop-order']);
})->with([
    'invalid name' => ['module:make 1blog', 'Invalid module name [1blog]'],
    'existing module' => ['module:make Blog', 'The directory [modules/blog] already exists.'],
    'existing directory' => ['module:make notes', 'The directory [modules/notes] already exists.'],
    'unsafe preset' => ['module:make wiki --preset=../api', 'the preset name may contain'],
    'missing preset' => ['module:make wiki --preset=api', 'the preset does not exist.'],
    'preset without composer.json' => ['module:make wiki --preset=broken', 'the template must contain a "composer.json"'],
]);

it('removes the module when its composer.json is invalid', function (string $composerJson, string $error) {
    $this->files(['stubs/module/default/composer.json.stub' => $composerJson]);

    $this->artisan('module:make wiki')->expectsOutputToContain($error)->assertFailed();

    Process::assertNothingRan();

    expect(file_exists($this->path('modules/wiki')))->toBeFalse()
        ->and(file_get_contents($this->path('composer.json')))->toBe($this->composerJson)
        ->and(array_keys(app(ModuleRepository::class)->manifest()))->toBe(['blog', 'shop-order']);
})->with([
    'another package name' => ['{"name": "app/other", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}', 'the package name must be [app/wiki], [app/other] given.'],
    'no namespace' => ['{"name": "{{ package }}"}', 'the first "autoload.psr-4" entry of composer.json must map'],
    'invalid JSON' => ['{"name": ', 'composer.json is not valid JSON'],
]);

it('refuses an invalid vendor', function () {
    $this->files(['config/modules.php' => '<?php return ["vendor" => "Acme Inc"];']);
    $this->reboot();

    $this->artisan('module:make wiki')->expectsOutputToContain('Invalid vendor [Acme Inc]')->assertFailed();

    expect(file_exists($this->path('modules/wiki')))->toBeFalse();
});

it('removes the module when writing it fails', function () {
    chmod($this->path('modules'), 0555);

    try {
        expect(fn () => $this->artisan('module:make wiki')->run())->toThrow(ErrorException::class);
    } finally {
        chmod($this->path('modules'), 0755);
    }

    Process::assertNothingRan();

    expect(file_exists($this->path('modules/wiki')))->toBeFalse()
        ->and(file_get_contents($this->path('composer.json')))->toBe($this->composerJson);
})->skip(fn () => DIRECTORY_SEPARATOR === '\\' || posix_getuid() === 0, 'File permissions are not enforced.');

it('uses the default template for --preset without a value', function () {
    $this->artisan('module:make wiki --preset --no-update')->assertSuccessful();

    expect(file_exists($this->path('modules/wiki/src/Models/.gitkeep')))->toBeTrue();
});
