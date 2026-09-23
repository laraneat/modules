<?php

use Illuminate\Support\Carbon;
use Laraneat\Modules\Support\Composer;

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->backupComposerJson();
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/../../fixtures/stubs/modules/valid/article',
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
        __DIR__ . '/../../fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/../../fixtures/stubs/modules/valid/empty',
        __DIR__ . '/../../fixtures/stubs/modules/valid/navigation',
    ]);

    $this->travelTo(Carbon::parse('2024-01-01'));
});

it('generates a "plain" module', function () {
    $this->instance(Composer::class, $this->mockComposer(['updatePackages' => true]));
    $this->artisan('module:make', [
        'name' => 'demo/article-comment',
        '--preset' => 'plain',
        '--entity' => 'ArticleComment',
    ])
        ->assertSuccessful();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
    assertsMatchesDirectorySnapshot($this->app->basePath('/modules/article-comment'));
});

it('generates a "base" module', function () {
    $this->instance(Composer::class, $this->mockComposer(['updatePackages' => true]));
    $this->artisan('module:make', [
        'name' => 'demo/article-comment',
        '--preset' => 'base',
        '--entity' => 'ArticleComment',
    ])
        ->expectsQuestion(
            'Enter the class name of the "Create permission action"',
            'Modules\\Authorization\\Actions\\CreatePermissionAction'
        )
        ->expectsQuestion(
            'Enter the class name of the "Create permission DTO"',
            'Modules\\Authorization\\DTO\\CreatePermissionDTO'
        )
        ->expectsQuestion(
            'Enter the class name of the "User model"',
            'Modules\\User\\Models\\User'
        )
        ->assertSuccessful();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
    assertsMatchesDirectorySnapshot($this->app->basePath('/modules/article-comment'));
});

it('generates a "api" module', function () {
    $this->instance(Composer::class, $this->mockComposer(['updatePackages' => true]));
    $this->artisan('module:make', [
        'name' => 'demo/article-comment',
        '--preset' => 'api',
        '--entity' => 'ArticleComment',
    ])
        ->expectsQuestion(
            'Enter the class name of the "Create permission action"',
            'Modules\\Authorization\\Actions\\CreatePermissionAction'
        )
        ->expectsQuestion(
            'Enter the class name of the "Create permission DTO"',
            'Modules\\Authorization\\DTO\\CreatePermissionDTO'
        )
        ->expectsQuestion(
            'Enter the class name of the "User model"',
            'Modules\\User\\Models\\User'
        )
        ->assertSuccessful();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
    assertsMatchesDirectorySnapshot($this->app->basePath('/modules/article-comment'));
});

it('displays an error message if the passed module name is not valid', function () {
    $this->artisan('module:make', [
        'name' => '1foo',
        '--preset' => 'plain',
        '--entity' => 'Foo',
    ])
        ->expectsOutputToContain("The module name passed is not valid!")
        ->assertFailed();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
});

it('displays an error message when a module with the same package name already exists', function () {
    $this->artisan('module:make', [
        'name' => 'laraneat/article',
        '--preset' => 'plain',
        '--entity' => 'Article',
    ])
        ->expectsOutputToContain("Module 'laraneat/article' already exist!")
        ->assertFailed();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
});

it('displays an error message when a module with the same folder name already exists', function () {
    $this->artisan('module:make', [
        'name' => 'demo/article',
        '--preset' => 'plain',
        '--entity' => 'Article',
    ])
        ->expectsOutputToContain("File already exists")
        ->assertFailed();

    assertMatchesFileSnapshot($this->app->basePath('/composer.json'));
});

it('rejects a vendor that is not a valid composer package name', function (string $name) {
    $composer = Mockery::mock(Composer::class);
    $composer->shouldNotReceive('updatePackages');
    $this->instance(Composer::class, $composer);
    $composerJsonBefore = file_get_contents($this->app->basePath('/composer.json'));

    $this->artisan('module:make', ['name' => $name, '--preset' => 'plain'])
        ->expectsOutputToContain('is not a valid composer package name')
        ->assertFailed();

    expect($this->app->basePath('/modules/blog'))->not->toBeDirectory()
        ->and(file_get_contents($this->app->basePath('/composer.json')))->toBe($composerJsonBefore);
})->with([
    'json injection' => ['evil","scripts":{"x":"y"},"a":"/blog'],
    'option-like vendor' => ['-evil/blog'],
]);

it('escapes the configured author when generating composer.json', function () {
    $this->instance(Composer::class, $this->mockComposer(['updatePackages' => true]));
    config([
        'modules.composer.author.name' => 'Conan O"Brien \\ Co',
        'modules.composer.author.email' => 'conan@example.com',
    ]);

    $this->artisan('module:make', ['name' => 'demo/blog', '--preset' => 'plain'])
        ->assertSuccessful();

    $composerJson = json_decode(file_get_contents($this->app->basePath('/modules/blog/composer.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($composerJson['authors'][0])->toBe(['name' => 'Conan O"Brien \\ Co', 'email' => 'conan@example.com'])
        ->and(array_keys($composerJson))->not->toContain('scripts');
});
