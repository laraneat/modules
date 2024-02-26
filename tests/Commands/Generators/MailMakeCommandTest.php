<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/modules'));
});

it('generates mail for the module', function () {
    $this->artisan('module:make:mail', [
        'name' => 'SomeAuthorMail',
        'module' => 'Author',
        '--subject' => 'Some testing subject',
        '--view' => 'author.mail.some-view',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath('/modules/Author/src/Mails/SomeAuthorMail.php');
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});
