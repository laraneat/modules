<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Manifest\ManifestCache;
use Symfony\Component\Process\Process;

function manifestEntry(string $path): array
{
    return [
        'package' => 'app/blog',
        'namespace' => 'Modules\\Blog',
        'path' => $path,
        'src' => 'src',
        'config' => ['blog'],
        'lang' => true,
        'json' => false,
        'views' => true,
        'migrations' => false,
        'routes' => ['api' => ['' => ['routes/api/posts.php']]],
        'seeders' => ['' => ['Modules\\Blog\\Database\\Seeders\\BlogSeeder']],
        'commands' => [],
    ];
}

it('writes the manifest as a PHP array with paths relative to the base path', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/bootstrap/cache/modules.php', $this->directory);

    $cache->write(['blog' => manifestEntry($this->directory.'/modules/blog')]);

    expect($cache->exists())->toBeTrue()
        ->and(require $this->directory.'/bootstrap/cache/modules.php')->toBe(['blog' => manifestEntry('modules/blog')])
        ->and(file_get_contents($this->directory.'/bootstrap/cache/modules.php'))->not->toContain($this->directory);
});

it('reads the manifest back with absolute paths', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/modules.php', $this->directory);

    $cache->write(['blog' => manifestEntry($this->directory.'/modules/blog')]);

    expect($cache->read())->toBe(['blog' => manifestEntry($this->directory.'/modules/blog')]);
});

it('works when the application is moved to another directory', function () {
    (new ManifestCache(new Filesystem, $this->directory.'/build/modules.php', $this->directory.'/build'))
        ->write(['blog' => manifestEntry($this->directory.'/build/modules/blog')]);

    rename($this->directory.'/build', $this->directory.'/release');

    expect((new ManifestCache(new Filesystem, $this->directory.'/release/modules.php', $this->directory.'/release'))->read())
        ->toBe(['blog' => manifestEntry($this->directory.'/release/modules/blog')]);
});

it('keeps paths outside of the base path absolute', function (string $path) {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/modules.php', $this->directory.'/app');

    $cache->write(['blog' => manifestEntry($path)]);

    expect((require $this->directory.'/modules.php')['blog']['path'])->toBe($path)
        ->and($cache->read()['blog']['path'])->toBe($path);
})->with([
    'unix' => ['/srv/shared/modules/blog'],
    'sibling with the same prefix' => [fn () => $this->directory.'/application/modules/blog'],
    'windows' => ['D:/shared/modules/blog'],
]);

it('creates the cache directory', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/missing/cache/modules.php', $this->directory);

    $cache->write([]);

    expect(require $this->directory.'/missing/cache/modules.php')->toBe([]);
});

it('writes the file when OPcache refuses to drop its compiled copy', function () {
    $script = $this->directory.'/write.php';
    $path = $this->directory.'/modules.php';
    file_put_contents($script, sprintf(<<<'PHP'
        <?php

        require %s;

        // As Laravel does: a warning that is not suppressed fails the command.
        set_error_handler(static fn (int $level, string $message) => (error_reporting() & $level)
            ? throw new ErrorException($message, 0, $level)
            : false);

        (new Laraneat\Modules\Manifest\ManifestCache(new Illuminate\Filesystem\Filesystem, %s, __DIR__))->write([]);
        PHP, var_export(dirname(__DIR__, 3).'/vendor/autoload.php', true), var_export($path, true)));

    $process = new Process([PHP_BINARY, '-d', 'opcache.enable_cli=1', '-d', 'opcache.restrict_api=/nonexistent', $script]);

    expect($process->run())->toBe(0, $process->getErrorOutput().$process->getOutput())
        ->and(require $path)->toBe([]);
})->skip(fn () => ! function_exists('opcache_invalidate'), 'OPcache is not loaded.');

it('reads what it wrote over a file that this process has read', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/modules.php', $this->directory);

    $cache->write(['blog' => manifestEntry($this->directory.'/modules/blog')]);
    // OPcache does not check files written in the last two seconds.
    touch($this->directory.'/modules.php', time() - 60);
    clearstatcache();

    expect(array_keys($cache->read()))->toBe(['blog']);

    $cache->write(['wiki' => manifestEntry($this->directory.'/modules/wiki')]);

    expect(array_keys($cache->read()))->toBe(['wiki']);
});

it('deletes the file', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/modules.php', $this->directory);
    $cache->write([]);

    $cache->delete();
    $cache->delete();

    expect($cache->exists())->toBeFalse()
        ->and(file_exists($this->directory.'/modules.php'))->toBeFalse();
});

it('normalizes a windows base path', function () {
    $cache = new ManifestCache(new Filesystem, $this->directory.'/modules.php', str_replace('/', '\\', $this->directory).'\\');

    $cache->write(['blog' => manifestEntry($this->directory.'/modules/blog')]);

    expect((require $this->directory.'/modules.php')['blog']['path'])->toBe('modules/blog')
        ->and($cache->path())->toBe($this->directory.'/modules.php');
});
