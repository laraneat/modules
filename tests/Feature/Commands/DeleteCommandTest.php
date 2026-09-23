<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Laraneat\Modules\Facades\Modules;

beforeEach(function () {
    $this->installModules();
    $this->composerJson = file_get_contents($this->path('composer.json'));
});

it('uninstalls the module with Composer, then deletes it', function () {
    $this->expectComposer(['remove', '--', 'app/blog'], during: function () {
        // Composer removes the "require" entry itself, while the module still exists.
        expect(is_dir($this->path('modules/blog')))->toBeTrue()
            ->and($this->readJson('composer.json')['require'])->toHaveKey('app/blog')
            ->and($this->readJson('composer.json'))->not->toHaveKey('autoload-dev.psr-4.Modules\\Blog\\Tests\\');
    });

    $this->artisan('module:delete blog')
        ->expectsConfirmation('Delete the [blog] module and all of its files?', 'yes')
        ->expectsOutputToContain('Module [blog] deleted.')
        ->assertSuccessful();

    expect(file_exists($this->path('modules/blog')))->toBeFalse()
        ->and(array_keys(Modules::all()))->toBe(['shop-order'])
        ->and($this->readJson('composer.json')['autoload-dev']['psr-4'])->toBe([]);
});

it('deletes without confirmation with --force', function () {
    $this->expectComposer(['remove', '--', 'app/shop-order']);

    $this->artisan('module:delete shop-order --force')->assertSuccessful();

    expect(file_exists($this->path('modules/shop-order')))->toBeFalse();
});

it('keeps the module when the deletion is not confirmed', function () {
    $this->artisan('module:delete blog')
        ->expectsConfirmation('Delete the [blog] module and all of its files?', 'no')
        ->expectsOutputToContain('The module was not deleted.')
        ->assertFailed();

    Process::assertNothingRan();

    expect(is_dir($this->path('modules/blog')))->toBeTrue()
        ->and(file_get_contents($this->path('composer.json')))->toBe($this->composerJson);
});

it('requires --force in non-interactive mode', function () {
    $this->artisan('module:delete blog --no-interaction')
        ->expectsOutputToContain('Use --force to delete a module in non-interactive mode.')
        ->assertFailed();

    Process::assertNothingRan();

    expect(is_dir($this->path('modules/blog')))->toBeTrue();
});

it('restores composer.json and keeps the module when Composer fails', function () {
    $this->expectComposer(['remove', '--', 'app/blog'], exitCode: 1);

    $this->artisan('module:delete blog --force')
        ->expectsOutputToContain('Composer failed to remove the module, nothing was deleted.')
        ->assertFailed();

    expect(is_dir($this->path('modules/blog')))->toBeTrue()
        ->and(file_get_contents($this->path('composer.json')))->toBe($this->composerJson)
        ->and(array_keys(Modules::all()))->toBe(['blog', 'shop-order']);
});

it('only edits composer.json with --no-update', function () {
    $this->artisan('module:delete blog --force --no-update')
        ->expectsOutputToContain('Uninstall it with: composer update app/blog')
        ->assertSuccessful();

    Process::assertNothingRan();

    expect(file_exists($this->path('modules/blog')))->toBeFalse()
        ->and(is_link($this->path('vendor/app/blog')))->toBeFalse()
        ->and(is_link($this->path('vendor/app/shop-order')))->toBeTrue()
        ->and($this->readJson('composer.json')['require'])->toBe([
            'php' => '^8.3',
            'app/shop-order' => '*@dev',
            'laravel/framework' => '^13.0',
        ]);
});

it('updates the module cache when it exists', function () {
    $this->artisan('module:cache');
    $this->expectComposer(['remove', '--', 'app/blog']);

    $this->artisan('module:delete blog --force')->assertSuccessful();

    expect(array_keys(require $this->path('bootstrap/cache/modules.php')))->toBe(['shop-order']);
});

it('refuses to delete a missing module', function (string $name) {
    $this->artisan('module:delete', ['name' => $name, '--force' => true])
        ->expectsOutputToContain("Module [{$name}] not found.")
        ->assertFailed();

    Process::assertNothingRan();

    expect(file_get_contents($this->path('composer.json')))->toBe($this->composerJson);
})->with(['notes', 'wiki', '../modules/blog']);

it('refuses to delete a module linked from elsewhere', function () {
    $target = $this->temporaryDirectory();
    file_put_contents($target.'/composer.json', '{"name": "app/linked", "autoload": {"psr-4": {"Modules\\\\Linked\\\\": "src/"}}}');
    symlink($target, $this->path('modules/linked'));
    $this->reboot();

    $this->artisan('module:delete linked --force')
        ->expectsOutputToContain('[modules/linked] is a symbolic link. Remove the module by hand.')
        ->assertFailed();

    Process::assertNothingRan();

    expect(file_exists($target.'/composer.json'))->toBeTrue();
})->skipOnWindows();

it('fails on an invalid composer.json of the application', function () {
    $this->files(['composer.json' => '[']);

    $this->artisan('module:delete blog --force')
        ->expectsOutputToContain('composer.json] is not valid JSON')
        ->assertFailed();

    Process::assertNothingRan();

    expect(is_dir($this->path('modules/blog')))->toBeTrue();
});

it('reports a module directory that can not be deleted', function () {
    $this->expectComposer(['remove', '--', 'app/blog']);
    chmod($this->path('modules/blog/config'), 0555);

    try {
        $this->artisan('module:delete blog --force')
            ->expectsOutputToContain('[modules/blog] could not be deleted completely. Delete it by hand.')
            ->assertFailed();
    } finally {
        chmod($this->path('modules/blog/config'), 0755);
    }
})->skip(fn () => DIRECTORY_SEPARATOR === '\\' || posix_getuid() === 0, 'File permissions are not enforced.');
