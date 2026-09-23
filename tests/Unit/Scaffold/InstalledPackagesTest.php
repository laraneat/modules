<?php

declare(strict_types=1);

use Laraneat\Modules\Scaffold\InstalledPackages;

const INSTALLED_BLOG = [
    'name' => 'app/blog',
    'version' => 'dev-main',
    'dist' => ['type' => 'path', 'url' => 'modules/blog'],
    'require' => ['php' => '^8.3'],
    'type' => 'library',
    'extra' => ['laravel' => ['providers' => ['Modules\\Blog\\BlogServiceProvider']]],
    'autoload' => ['psr-4' => ['Modules\\Blog\\' => 'src/']],
];

it('reads the packages of composer 2', function () {
    $vendor = $this->files(['composer/installed.json' => json_encode(['packages' => [INSTALLED_BLOG], 'dev' => true])]);

    $installed = InstalledPackages::read($vendor);

    expect($installed->has('app/blog'))->toBeTrue()
        ->and($installed->has('app/shop'))->toBeFalse()
        ->and($installed->path('app/blog'))->toBe($vendor.'/app/blog');
});

it('reads the packages of composer 1', function () {
    $vendor = $this->files(['composer/installed.json' => json_encode([INSTALLED_BLOG])]);

    expect(InstalledPackages::read($vendor)->has('app/blog'))->toBeTrue();
});

it('has no packages without installed.json', function (?string $contents) {
    if ($contents !== null) {
        $this->files(['composer/installed.json' => $contents]);
    }

    expect(InstalledPackages::read($this->directory)->has('app/blog'))->toBeFalse();
})->with([
    'missing' => [null],
    'invalid' => ['{'],
    'unexpected' => ['{"packages": [42, {"version": "1.0"}]}'],
]);

it('compares the installed metadata with composer.json', function (array $composerJson, bool $current) {
    $installed = InstalledPackages::read($this->files(['composer/installed.json' => json_encode(['packages' => [INSTALLED_BLOG]])]));

    expect($installed->isCurrent('app/blog', $composerJson))->toBe($current);
})->with([
    'same' => [['name' => 'app/blog', 'require' => ['php' => '^8.3'], 'extra' => INSTALLED_BLOG['extra'], 'autoload' => INSTALLED_BLOG['autoload'], 'description' => 'changed'], true],
    'new provider' => [['require' => ['php' => '^8.3'], 'extra' => ['laravel' => ['providers' => ['A', 'B']]], 'autoload' => INSTALLED_BLOG['autoload']], false],
    'new namespace' => [['require' => ['php' => '^8.3'], 'extra' => INSTALLED_BLOG['extra'], 'autoload' => ['psr-4' => ['Modules\\Blog\\' => 'src/', 'X\\' => 'x/']]], false],
    'new dependency' => [['require' => ['php' => '^8.3', 'acme/lib' => '^1'], 'extra' => INSTALLED_BLOG['extra'], 'autoload' => INSTALLED_BLOG['autoload']], false],
]);

it('is not current when the package is not installed', function () {
    expect(InstalledPackages::read($this->directory)->isCurrent('app/blog', []))->toBeFalse();
});
