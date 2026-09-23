<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Laraneat\Modules\Exceptions\InvalidName;
use Laraneat\Modules\Scaffold\ComposerRunner;

use function Illuminate\Support\php_binary;

it('runs the composer.phar of the application with the current PHP binary', function () {
    $this->files(['composer.phar' => '']);
    Process::fake(['*' => Process::result()]);

    expect(app(ComposerRunner::class)->remove(['app/blog', 'app/shop-order']))->toBeTrue();

    Process::assertRan(fn (PendingProcess $process): bool => $process->command === [php_binary(), 'composer.phar', 'remove', '--', 'app/blog', 'app/shop-order']);
});

it('tells whether Composer succeeded', function (int $exitCode, bool $successful) {
    $this->expectComposer(['update', '--', 'app/blog'], exitCode: $exitCode);

    expect(app(ComposerRunner::class)->update(['app/blog']))->toBe($successful);
})->with([
    'success' => [0, true],
    'failure' => [1, false],
]);

it('refuses package names that are not package names', function (string $package) {
    $runner = app(ComposerRunner::class);

    expect(fn () => $runner->update([$package]))->toThrow(InvalidName::class)
        ->and(fn () => $runner->commandLine('update', [$package]))->toThrow(InvalidName::class);

    Process::assertNothingRan();
})->with(['--no-scripts', 'app/blog --dev', 'app/blog;rm', 'App/Blog', 'blog']);

it('prints the command to run by hand', function () {
    expect(app(ComposerRunner::class)->commandLine('update', ['app/blog', 'app/shop-order']))
        ->toBe('composer update app/blog app/shop-order');
});
