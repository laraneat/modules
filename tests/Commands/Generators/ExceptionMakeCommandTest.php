<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates exception for the module', function () {
    $this->artisan('module:make:exception', [
        'name' => 'SomeAuthorException',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Exceptions/SomeAuthorException.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('rejects names that would escape the component directory', function (string $name) {
    $filesBefore = getRelativeFilePathsInDirectory($this->app->basePath());

    $this->artisan('module:make:exception', [
        'name' => $name,
        'module' => 'Author',
    ])
        ->expectsOutputToContain('is not a valid PHP class name')
        ->assertFailed();

    expect(getRelativeFilePathsInDirectory($this->app->basePath()))->toBe($filesBefore);
})->with([
    '../../../../SecOne',
    'Nested/../../Escape',
    'Foo/Bar-Baz',
    'Foo/./Bar',
]);

it('accepts sub-namespaces in the name', function () {
    $this->artisan('module:make:exception', [
        'name' => 'Nested\\Deeper/SomeAuthorException',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    assertFileExists($this->app->basePath('/modules/author/src/Exceptions/Nested/Deeper/SomeAuthorException.php'));
});
