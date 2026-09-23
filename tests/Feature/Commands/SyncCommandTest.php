<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

it('adds every module to composer.json and installs them', function () {
    $this->expectComposer(['update', '--', 'app/blog', 'app/shop-order'], during: function () {
        expect($this->readJson('composer.json')['require'])->toHaveKeys(['app/blog', 'app/shop-order']);
    });

    $this->artisan('module:sync')
        ->expectsOutputToContain('Modules are in sync with Composer.')
        ->assertSuccessful();

    expect($this->readJson('composer.json'))
        ->require->toBe([
            'php' => '^8.3',
            'app/blog' => '*@dev',
            'app/shop-order' => '*@dev',
            'laravel/framework' => '^13.0',
        ])
        ->repositories->toBe([
            ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
            ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*']],
            ['packagist.org' => false],
        ])
        ->{'autoload-dev'}->toBe(['psr-4' => ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']]);
});

it('does nothing when the modules are in sync', function () {
    $this->installModules();
    $composerJson = file_get_contents($this->path('composer.json'));

    $this->artisan('module:sync')
        ->expectsOutputToContain('Modules are in sync with Composer.')
        ->assertSuccessful();

    Process::assertNothingRan();

    expect(file_get_contents($this->path('composer.json')))->toBe($composerJson);
});

it('installs the modules whose composer.json changed', function (array $change) {
    $this->installModules();
    $composerJson = $this->readJson('modules/shop-order/composer.json');
    $this->files(['modules/shop-order/composer.json' => json_encode(array_replace_recursive($composerJson, $change))]);

    $this->expectComposer(['update', '--', 'app/shop-order']);

    $this->artisan('module:sync')->assertSuccessful();
})->with([
    'require' => [['require' => ['php' => '^8.4']]],
    'autoload' => [['autoload' => ['files' => ['helpers.php']]]],
    'extra' => [['extra' => ['laravel' => ['providers' => ['Modules\\ShopOrder\\Providers\\ShopOrderServiceProvider']]]]],
]);

it('ignores the key order of the installed metadata', function () {
    $this->installModules();
    $installed = $this->readJson('vendor/composer/installed.json');
    $installed['packages'][0]['autoload']['psr-4'] = array_reverse($installed['packages'][0]['autoload']['psr-4'], true);
    $this->files(['vendor/composer/installed.json' => json_encode($installed)]);

    $this->artisan('module:sync')->assertSuccessful();

    Process::assertNothingRan();
});

it('installs a module missing from installed.json', function () {
    $this->installModules(['blog']);
    $this->expectComposer(['update', '--', 'app/shop-order']);

    $this->artisan('module:sync')->assertSuccessful();
});

it('installs a module that is not required yet', function () {
    $this->installModules();
    $composerJson = $this->readJson('composer.json');
    unset($composerJson['require']['app/blog']);
    $this->files(['composer.json' => json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)]);

    $this->expectComposer(['update', '--', 'app/blog']);

    $this->artisan('module:sync')->assertSuccessful();

    expect($this->readJson('composer.json')['require'])->toHaveKey('app/blog');
});

it('picks up a module created by hand', function () {
    $this->installModules();
    $this->artisan('module:cache');
    $this->files(['modules/wiki/composer.json' => '{"name": "app/wiki", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}']);

    $this->expectComposer(['update', '--', 'app/wiki']);

    $this->artisan('module:sync')->assertSuccessful();

    expect(array_keys(require $this->path('bootstrap/cache/modules.php')))->toBe(['blog', 'shop-order', 'wiki']);
});

it('only edits composer.json with --no-update', function () {
    $this->artisan('module:sync --no-update')
        ->expectsOutputToContain('Install the outdated modules with: composer update app/blog app/shop-order')
        ->assertSuccessful();

    Process::assertNothingRan();

    expect($this->readJson('composer.json')['require'])->toHaveKeys(['app/blog', 'app/shop-order']);
});

it('fails when Composer fails', function () {
    $this->expectComposer(['update', '--', 'app/blog', 'app/shop-order'], exitCode: 1);

    $this->artisan('module:sync')
        ->expectsOutputToContain('Composer failed to install the modules. Fix the problem and run: composer update app/blog app/shop-order')
        ->assertFailed();
});

it('fails on an invalid composer.json of the application', function () {
    $this->files(['composer.json' => '{"repositories": "packagist"}']);

    $this->artisan('module:sync')
        ->expectsOutputToContain('"repositories" must be an array or an object.')
        ->assertFailed();

    Process::assertNothingRan();

    expect(file_get_contents($this->path('composer.json')))->toBe('{"repositories": "packagist"}');
});

it('uses the vendor directory of the Composer config', function () {
    $this->installModules();
    rename($this->path('vendor'), $this->path('libraries'));
    $composerJson = $this->readJson('composer.json');
    $composerJson['config']['vendor-dir'] = 'libraries';
    $this->files(['composer.json' => json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"]);

    $this->artisan('module:sync')->assertSuccessful();

    Process::assertNothingRan();
});
