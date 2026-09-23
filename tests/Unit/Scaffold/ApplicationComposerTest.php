<?php

declare(strict_types=1);

use Laraneat\Modules\Module;
use Laraneat\Modules\Scaffold\ApplicationComposer;

function blogModule(string $basePath): Module
{
    return new Module('blog', 'app/blog', 'Modules\\Blog', $basePath.'/modules/blog', $basePath.'/modules/blog/src');
}

it('maps the dev autoload of a module to paths of the application', function (array $autoloadDev, array $expected) {
    $this->files(['modules/blog/composer.json' => json_encode(['name' => 'app/blog', 'autoload-dev' => ['psr-4' => $autoloadDev]])]);

    expect((new ApplicationComposer($this->directory))->autoloadDev(blogModule($this->directory)))->toBe($expected);
})->with([
    'one path' => [['Modules\\Blog\\Tests\\' => 'tests/'], ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    'dot slash' => [['Modules\\Blog\\Tests\\' => './tests/'], ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    'several paths' => [['Modules\\Blog\\Tests\\' => ['tests/', 'fixtures/']], ['Modules\\Blog\\Tests\\' => ['modules/blog/tests/', 'modules/blog/fixtures/']]],
    'one path in a list' => [['Modules\\Blog\\Tests\\' => ['tests/']], ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    'invalid paths' => [['Modules\\Blog\\Tests\\' => [1, null, 'tests/'], 'Empty\\' => [], 'Number\\' => 5], ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    'nothing' => [[], []],
]);

it('reads no dev autoload from a module without it', function () {
    $this->files(['modules/blog/composer.json' => '{"name": "app/blog"}']);

    expect((new ApplicationComposer($this->directory))->autoloadDev(blogModule($this->directory)))->toBe([]);
});

it('adds a module and tells whether composer.json changed', function () {
    $this->files([
        'composer.json' => '{"name": "demo/app"}',
        'modules/blog/composer.json' => '{"name": "app/blog", "autoload-dev": {"psr-4": {"Modules\\\\Blog\\\\Tests\\\\": "tests/"}}}',
    ]);
    $composer = new ApplicationComposer($this->directory.'/');
    $root = $composer->composerJson();

    expect($composer->addModule($root, blogModule($this->directory), $this->directory.'/modules'))->toBeTrue()
        ->and($composer->addModule($root, blogModule($this->directory), $this->directory.'/modules'))->toBeFalse()
        ->and($root->toArray())->toMatchArray([
            'require' => ['app/blog' => '*@dev'],
            'autoload-dev' => ['psr-4' => ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
        ])
        ->and($root->get('repositories.0.url'))->toBe('modules/*')
        ->and($composer->composerJsonPath())->toBe($this->directory.'/composer.json');
});

it('makes paths inside the application relative', function (string $path, string $expected) {
    $composer = new ApplicationComposer($this->directory);

    expect($composer->relative(str_replace('{base}', $this->directory, $path)))->toBe(str_replace('{base}', $this->directory, $expected));
})->with([
    'inside' => ['{base}/modules/blog', 'modules/blog'],
    'trailing slash' => ['{base}/modules/', 'modules'],
    'the application' => ['{base}', ''],
    'a sibling with the same prefix' => ['{base}-other/modules', '{base}-other/modules'],
    'outside' => ['/elsewhere/modules', '/elsewhere/modules'],
]);

it('reads the installed packages of the vendor directory', function (?string $vendorDir, string $installed) {
    $this->files([
        'composer.json' => json_encode($vendorDir === null ? ['name' => 'demo/app'] : ['config' => ['vendor-dir' => str_replace('{base}', $this->directory, $vendorDir)]]),
        $installed.'/composer/installed.json' => '{"packages": [{"name": "app/blog"}]}',
    ]);
    $composer = new ApplicationComposer($this->directory);
    $packages = $composer->installed($composer->composerJson());

    expect($packages->has('app/blog'))->toBeTrue()
        ->and($packages->path('app/blog'))->toBe($this->directory.'/'.$installed.'/app/blog');
})->with([
    'default' => [null, 'vendor'],
    'relative' => ['libraries', 'libraries'],
    'absolute' => ['{base}/deps', 'deps'],
]);
