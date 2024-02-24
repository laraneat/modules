<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates factory for the module', function () {
    $this->artisan('module:make:factory', [
        'name' => 'SomeAuthorFactory',
        'module' => 'Author',
        '--model' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Data/Factories/SomeAuthorFactory.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
