<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
    ], $this->app->basePath('/modules'));
});

it('generates rule for the module', function () {
    $this->artisan('module:make:rule', [
        'name' => 'SomeAuthorRule',
        'module' => 'Author',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/Rules/SomeAuthorRule.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
