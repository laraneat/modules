<?php

use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
});

it('generates mail for the module', function () {
    $this->artisan('module:make:mail', [
        'name' => 'SomeAuthorMail',
        'module' => 'Author',
        '--subject' => 'Some testing subject',
        '--view' => 'author.mail.some-view'
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/app/Modules/Author/Mails/SomeAuthorMail.php');
    expect(is_file($filePath))->toBeTrue();
    assertMatchesFileSnapshot($filePath);
});
