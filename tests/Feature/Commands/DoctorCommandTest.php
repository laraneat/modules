<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function doctor(): array
{
    $status = Artisan::call('module:doctor');

    return [$status, preg_replace('/ +$/m', '', str_replace("\r\n", "\n", Artisan::output()))];
}

/**
 * Windows removes a link to a directory with rmdir().
 */
function removeLink(string $path): void
{
    DIRECTORY_SEPARATOR === '\\' ? rmdir($path) : unlink($path);
}

it('finds no problems in installed modules', function () {
    $this->installModules();

    [$status, $output] = doctor();

    expect($status)->toBe(0)
        ->and($output)->toContain('Configuration', 'blog', 'shop-order', 'No problems found.')
        ->not->toContain('FAIL', 'WARN');
});

it('reports modules that are not installed', function () {
    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain(
            '✗ The application does not require [app/blog]: run "php artisan module:sync".',
            '✗ [app/blog] can be installed from Packagist: run "php artisan module:sync" to exclude the vendor.',
            '✗ [app/blog] is not installed: run "php artisan module:sync".',
            '! The application does not autoload [Modules\\Blog\\Tests\\] for development: run "php artisan module:sync".',
            '✗ [app/shop-order] is not installed',
            'Found 6 error(s) and 1 warning(s).',
        );
});

it('reports a package linked elsewhere', function () {
    $this->installModules();
    removeLink($this->path('vendor/app/blog'));
    symlink($this->path('modules/shop-order'), $this->path('vendor/app/blog'));

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain('✗ [vendor/app/blog] is not a link to [modules/blog].');
});

it('warns about a mirrored package', function () {
    $this->installModules();
    removeLink($this->path('vendor/app/blog'));
    mkdir($this->path('vendor/app/blog'));

    [$status, $output] = doctor();

    expect($status)->toBe(0)
        ->and($output)->toContain('! [vendor/app/blog] is a copy, not a link to [modules/blog]: changes of the module need "composer update".');
});

it('reports a missing installed package', function () {
    $this->installModules();
    removeLink($this->path('vendor/app/blog'));

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain('✗ [vendor/app/blog] is not a link to [modules/blog].');
});

it('reports a package installed from another repository', function (array $dist) {
    $this->installModules();
    removeLink($this->path('vendor/app/blog'));
    mkdir($this->path('vendor/app/blog'));
    $installed = $this->readJson('vendor/composer/installed.json');
    $installed['packages'][0]['dist'] = $dist;
    $this->files(['vendor/composer/installed.json' => json_encode($installed)]);

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain('✗ [vendor/app/blog] is not a link to [modules/blog].');
})->with([
    'Packagist' => [['type' => 'zip', 'url' => 'https://api.github.com/repos/app/blog/zipball/1', 'reference' => '1']],
    'another path' => [['type' => 'path', 'url' => 'modules/shop-order', 'reference' => null]],
]);

it('reports outdated installed metadata', function () {
    $this->installModules();
    $this->files(['modules/blog/composer.json' => json_encode([...$this->readJson('modules/blog/composer.json'), 'require' => ['php' => '^8.4']])]);

    [$status, $output] = doctor();

    expect($status)->toBe(0)
        ->and($output)->toMatch('/^  blog \\.+ WARN\n    ! composer\\.json changed since the module was installed: run "php artisan module:sync"\\.$/m')
        ->toMatch('/^  shop-order \\.+ OK$/m')
        ->toContain('Found 1 warning(s).');
});

it('reports problems of the module structure', function () {
    $this->installModules();
    $composerJson = $this->readJson('modules/blog/composer.json');
    $composerJson['autoload']['psr-4'] = ['Modules\\Blog\\' => 'app/', 'Modules\\Blog\\Database\\Factories\\' => 'database/Factories/'];
    $composerJson['extra'] = ['laravel' => ['providers' => ['Modules\\Blog\\Providers\\MissingServiceProvider', 42]]];
    $this->files([
        'modules/blog/composer.json' => json_encode($composerJson),
        'modules/blog/routes/zeta/users.php' => '<?php',
        'modules/blog/routes/admin/users.php' => '<?php',
        'modules/blog/routes/web/.hidden/secret.php' => '<?php',
        'modules/blog/tests/routes/fixture.php' => '<?php',
        'modules/blog/vendor/acme/lib/routes/web.php' => '<?php',
        'modules/blog/node_modules/lib/routes/web.php' => '<?php',
    ]);
    $this->reboot();

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain(
            '✗ The directory of the [Modules\\Blog] namespace does not exist.',
            '✗ "autoload.psr-4" must map [Modules\\Blog\\Database\\Factories\\] to [database/factories/].',
            '✗ "autoload.psr-4" must map [Modules\\Blog\\Database\\Seeders\\] to [database/seeders/].',
            '✗ The provider [Modules\\Blog\\Providers\\MissingServiceProvider] does not exist.',
            '✗ The provider [int] does not exist.',
            "    ! The route file [routes/admin/users.php] is not in a route group of config/modules.php.\n    ! The route file [routes/zeta/users.php] is not in a route group of config/modules.php.",
        )
        ->toMatch('/^  blog \\.+ FAIL$/m')
        ->not->toContain('secret.php', 'fixture.php', 'lib/routes', 'routes/api/posts.php');
});

it('skips directories it can not read', function () {
    $this->installModules();
    $this->files(['modules/blog/private/routes/web.php' => '<?php']);
    chmod($this->path('modules/blog/private'), 0);

    try {
        [$status, $output] = doctor();
    } finally {
        chmod($this->path('modules/blog/private'), 0755);
    }

    expect($status)->toBe(0)->and($output)->toContain('No problems found.');
})->skip(fn () => DIRECTORY_SEPARATOR === '\\' || posix_getuid() === 0, 'File permissions are not enforced.');

it('accepts equivalent autoload paths and skips directories a module does not have', function () {
    // shop-order has no database/factories directory.
    $composerJson = $this->readJson('modules/shop-order/composer.json');
    $composerJson['autoload']['psr-4'] = ['Modules\\ShopOrder\\' => './src', 'Modules\\ShopOrder\\Database\\Seeders\\' => ['lib/', './database/seeders']];
    $composerJson['extra'] = ['laravel' => ['providers' => ['Modules\\ShopOrder\\Providers\\MissingServiceProvider']]];
    $this->files(['modules/shop-order/composer.json' => json_encode($composerJson)]);
    $this->installModules();

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toMatch('/^  shop-order \\.+ FAIL\n    ✗ The provider \\[Modules\\\\ShopOrder\\\\Providers\\\\MissingServiceProvider\\] does not exist\\.\n\n/m')
        ->toContain('Found 1 error(s) and 0 warning(s).');
});

it('reports problems of the configuration', function () {
    $this->installModules();
    $this->files([
        'config/modules.php' => '<?php return ["paths" => []];',
        'config/octane.php' => '<?php return ["watch" => ["app"]];',
    ]);
    $this->reboot();
    $this->artisan('module:cache');
    $this->files(['modules/wiki/composer.json' => '{"name": "app/wiki", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}']);

    [$status, $output] = doctor();

    expect($status)->toBe(0)
        ->and($output)->toContain(
            '! Unknown keys in config/modules.php: paths.',
            '! The module cache is outdated: run "php artisan module:cache".',
        )
        ->not->toContain('octane:start');
});

it('warns when Octane can not watch the modules', function () {
    $modules = $this->temporaryDirectory($this->path('modules'));
    $this->files([
        'config/modules.php' => '<?php return ["path" => "'.$modules.'"];',
        'config/octane.php' => '<?php return ["watch" => ["app"]];',
    ]);
    $this->reboot();

    [, $output] = doctor();

    expect($output)->toContain('! The modules path is outside of the application, so "octane:start --watch" does not watch it.');
});

it('fails on an invalid composer.json of the application', function () {
    $this->files(['composer.json' => '[']);

    [$status, $output] = doctor();

    expect($status)->toBe(1)
        ->and($output)->toContain('composer.json] is not valid JSON');
});
