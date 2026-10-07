<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Manifest\ManifestBuilder;
use Laraneat\Modules\Manifest\ManifestCache;
use Laraneat\Modules\ModuleRepository;

/*
 * Which repository of a process reads the manifest cache file: every application has its own.
 */

beforeEach(function () {
    $modules = $this->directory.'/modules';
    $this->module = function (string $name) use ($modules): void {
        mkdir($modules.'/'.$name, recursive: true);
        file_put_contents($modules.'/'.$name.'/composer.json', '{"name": "app/'.$name.'", "autoload": {"psr-4": {"Modules\\\\'.ucfirst($name).'\\\\": "src/"}}}');
    };
    $this->repository = fn (): ModuleRepository => new ModuleRepository(
        new ManifestBuilder($modules, [], 'Console\\Commands'),
        new ManifestCache(new Filesystem, $this->directory.'/cache/modules.php', $this->directory),
    );

    // The cache file lists "blog"; "wiki" is added after it was written.
    ($this->module)('blog');
    ($this->repository)()->cache();
    ($this->module)('wiki');
    ModuleRepository::flushState();
});

afterEach(function () {
    ModuleRepository::flushState();
});

it('reads the cache file in the first application of the process only', function () {
    $first = ($this->repository)();
    $second = ($this->repository)();

    expect($first->isCached())->toBeTrue()
        ->and(array_keys($first->all()))->toBe(['blog'])
        ->and($second->isCached())->toBeFalse()
        ->and(array_keys($second->all()))->toBe(['blog', 'wiki'])
        ->and(array_keys($first->manifest()))->toBe(['blog']);
});

it('builds the manifest once for the later applications', function () {
    ($this->repository)();
    $second = ($this->repository)();

    expect(array_keys($second->manifest()))->toBe(['blog', 'wiki']);

    ($this->module)('forum');

    expect(array_keys($second->manifest()))->toBe(['blog', 'wiki'])
        ->and(array_keys(($this->repository)()->manifest()))->toBe(['blog', 'wiki']);
});

it('reads the cache file again after the state is flushed', function () {
    ($this->repository)();
    ModuleRepository::flushState();

    $next = ($this->repository)();

    expect($next->isCached())->toBeTrue()
        ->and(array_keys($next->all()))->toBe(['blog']);
});

it('writes the cache file from any application and forgets what was read from it', function () {
    $first = ($this->repository)();
    $second = ($this->repository)();
    $first->manifest();

    $second->cache();
    $first->forget();

    expect(array_keys($first->all()))->toBe(['blog', 'wiki'])
        ->and($first->isCached())->toBeTrue()
        ->and($first->isCacheStale())->toBeFalse();
});
