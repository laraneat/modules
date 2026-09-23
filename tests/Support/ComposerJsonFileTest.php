<?php

use Laraneat\Modules\Support\ComposerJsonFile;

/**
 * @param array<string, mixed> $content
 * @return array<string, mixed>
 */
function addModuleToComposerJson(array $content, string $packageName = 'app/blog', int $times = 1): array
{
    $path = sys_get_temp_dir() . '/laraneat-composer-' . uniqid() . '.json';
    file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT));

    try {
        for ($i = 0; $i < $times; $i++) {
            ComposerJsonFile::create($path)->addModule($packageName, 'modules/blog')->save();
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } finally {
        @unlink($path);
    }
}

it('adds the module, its path repository and excludes its vendor from packagist', function () {
    $composerJson = addModuleToComposerJson(['name' => 'demo/app', 'require' => ['php' => '^8.3']]);

    expect($composerJson['require'])->toBe(['php' => '^8.3', 'app/blog' => '*'])
        ->and($composerJson['repositories'])->toBe([
            ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
            ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*']],
            ['packagist.org' => false],
        ]);
});

it('is idempotent', function () {
    $once = addModuleToComposerJson(['name' => 'demo/app']);
    $twice = addModuleToComposerJson(['name' => 'demo/app'], times: 2);

    expect($twice)->toBe($once);
});

it('extends an existing packagist repository instead of adding another one', function () {
    $composerJson = addModuleToComposerJson([
        'repositories' => [
            ['type' => 'composer', 'url' => 'https://repo.packagist.org/', 'exclude' => ['other/*']],
            ['packagist.org' => false],
        ],
    ]);

    expect($composerJson['repositories'])->toBe([
        ['type' => 'composer', 'url' => 'https://repo.packagist.org/', 'exclude' => ['other/*', 'app/*']],
        ['packagist.org' => false],
        ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
    ]);
});

it('leaves a packagist repository restricted with "only" untouched', function () {
    $repository = ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'only' => ['laravel/*']];
    $composerJson = addModuleToComposerJson(['repositories' => [$repository, ['packagist.org' => false]]]);

    expect($composerJson['repositories'][0])->toBe($repository);
});

it('does not re-enable packagist when it is disabled', function () {
    $composerJson = addModuleToComposerJson(['repositories' => [['packagist.org' => false]]]);

    expect($composerJson['repositories'])->toBe([
        ['packagist.org' => false],
        ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
    ]);
});

it('supports repositories defined as an object', function () {
    $composerJson = addModuleToComposerJson([
        'repositories' => ['private' => ['type' => 'composer', 'url' => 'https://repo.example.com']],
    ]);

    expect($composerJson['repositories'])->toBe([
        'private' => ['type' => 'composer', 'url' => 'https://repo.example.com'],
        0 => ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]],
        'packagist' => ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*']],
        'packagist.org' => false,
    ]);
});
