<?php

declare(strict_types=1);

use Laraneat\Modules\Exceptions\InvalidName;
use Laraneat\Modules\Scaffold\Names;

it('normalizes module names to kebab case', function (string $name, string $module) {
    expect(Names::module($name))->toBe($module);
})->with([
    ['blog', 'blog'],
    ['Blog', 'blog'],
    ['shop-order', 'shop-order'],
    ['ShopOrder', 'shop-order'],
    ['shopOrder', 'shop-order'],
    ['shop_order', 'shop-order'],
    ['shop order', 'shop-order'],
    ['  blog  ', 'blog'],
    ['api-v2', 'api-v2'],
    ['blog2', 'blog2'],
    ['list', 'list'],
    'leading dash' => ['-blog', 'blog'],
    'trailing nul byte' => ["blog\0", 'blog'],
]);

it('rejects module names that are not safe', function (string $name) {
    expect(fn () => Names::module($name))->toThrow(InvalidName::class, 'Invalid module name');
})->with([
    '',
    ' ',
    '../etc',
    '..',
    'blog/../../etc',
    'blog\\evil',
    '2fa',
    'blog"',
    "blog'",
    'bl$og',
    'блог',
    "bl\0og",
    'a/b',
]);

it('validates vendors', function (string $vendor, bool $valid) {
    $validate = fn () => Names::vendor($vendor);

    $valid ? expect($validate())->toBe($vendor) : expect($validate)->toThrow(InvalidName::class, 'Invalid vendor');
})->with([
    ['app', true],
    ['acme-corp', true],
    ['acme.corp', true],
    ['acme_corp', true],
    ['2acme', true],
    ['App', false],
    ['-app', false],
    ['app-', false],
    ['a/b', false],
    ['', false],
    ['app"', false],
    ['app,"evil":"1', false],
]);

it('builds package names', function () {
    expect(Names::package('app', 'ShopOrder'))->toBe('app/shop-order');
});

it('validates package names with the Composer format', function (string $package, bool $valid) {
    expect(Names::isPackage($package))->toBe($valid);

    $valid
        ? expect(Names::assertPackage($package))->toBe($package)
        : expect(fn () => Names::assertPackage($package))->toThrow(InvalidName::class, 'Invalid package name');
})->with([
    ['app/blog', true],
    ['app/shop-order', true],
    ['acme.corp/shop--order', true],
    ['app/blog_2', true],
    ['app', false],
    ['App/blog', false],
    ['app/-blog', false],
    ['app/blog/extra', false],
    ['--dev', false],
    ['-app/blog', false],
    ["app/blog\n", false],
    ['app/blog ', false],
]);

it('builds the namespace of a module', function (string $prefix, string $module, string $namespace) {
    expect(Names::namespace($prefix, $module))->toBe($namespace);
})->with([
    ['Modules', 'blog', 'Modules\\Blog'],
    ['Modules', 'shop-order', 'Modules\\ShopOrder'],
    ['\\App\\Modules\\', 'shop-order', 'App\\Modules\\ShopOrder'],
]);

it('rejects an invalid namespace prefix', function (string $prefix) {
    expect(fn () => Names::namespace($prefix, 'blog'))->toThrow(InvalidName::class, 'Invalid namespace');
})->with(['', 'App\\\\Modules', '1Modules', 'App-Modules', 'App\\Modules;']);

it('validates namespaces', function (string $namespace, bool $valid) {
    expect(Names::isNamespace($namespace))->toBe($valid);
})->with([
    ['Modules', true],
    ['Modules\\Blog', true],
    ['_Modules\\Blog_2', true],
    ['Modules\\', false],
    ['\\Modules', false],
    ['Modules\\2Blog', false],
    ['', false],
]);
