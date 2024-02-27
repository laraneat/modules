<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
    ], $this->app->basePath('/modules'));
});

it('generates console command for the module', function () {
    $this->artisan('module:make:command', [
        'name' => 'SomeAuthorCommand',
        'module' => 'Author',
        '--signature' => 'author:some-command {--foo : foo option}',
        '--description' => 'Some author command description',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/author/src/UI/CLI/Commands/SomeAuthorCommand.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
