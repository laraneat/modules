<?php

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Support\Composer;
use Symfony\Component\Process\Process;

function recordingComposer(): Composer
{
    return new class (new Filesystem(), sys_get_temp_dir()) extends Composer {
        /** @var array<int, array<int, string>> */
        public array $commands = [];

        protected function getProcess(array $command, array $env = [])
        {
            $this->commands[] = $command;

            return new Process([PHP_BINARY, '-r', 'exit(0);']);
        }
    };
}

it('passes package names after "--" so they can never be parsed as options', function (string $method, string $action) {
    $composer = recordingComposer();

    expect($composer->{$method}(['vendor/module-a', 'vendor/module-b']))->toBeTrue()
        ->and(array_slice($composer->commands[0], -4))->toBe([$action, '--', 'vendor/module-a', 'vendor/module-b']);
})->with([
    ['updatePackages', 'update'],
    ['removePackages', 'remove'],
]);

it('rejects invalid package names without running composer', function (string $packageName) {
    $composer = recordingComposer();

    expect(fn () => $composer->updatePackages([$packageName]))->toThrow(ComposerException::class)
        ->and(fn () => $composer->removePackages([$packageName]))->toThrow(ComposerException::class)
        ->and($composer->commands)->toBe([]);
})->with([
    '--no-plugins',
    '-vvv/module',
    'vendor',
    'Vendor/Module',
    'vendor/module name',
    'vendor/../module',
    'evil","scripts":{"x":"y"},"a":"/blog',
]);

it('validates package names with the composer rules', function (string $packageName, bool $valid) {
    expect(Composer::isValidPackageName($packageName))->toBe($valid);
})->with([
    ['laraneat/article', true],
    ['my-vendor/article-category', true],
    ['vendor.name/module_name', true],
    ['vendor/module--double', true],
    ['vendor/module-', false],
    ['vendor/Module', false],
    ["vendor/module\n", false],
]);
