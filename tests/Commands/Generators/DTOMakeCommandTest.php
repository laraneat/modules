<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates dto for the module', function () {
    $this->artisan('module:make:dto', [
        'name' => 'SomeAuthorDTO',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/DTO/SomeAuthorDTO.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
