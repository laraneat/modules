<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates console command for the module', function () {
    $this->artisan('module:make:command', [
        'name' => 'SomeAuthorCommand',
        'module' => 'Author',
        '--signature' => 'author:some-command {--foo : foo option}',
        '--description' => 'Some author command description',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/UI/CLI/Commands/SomeAuthorCommand.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
