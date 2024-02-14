<?php

beforeEach(function() {
    $this->setVendorModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);
    $this->setAppModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Article'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Author'),
    ]);
});

it('outputs a table of app modules', function () {
    $this->artisan('module:list --app')
        ->expectsTable(['Package Name', 'Namespace', 'Path'], [
            ['laraneat/article', 'App\\Modules\\Article', realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Article')],
            ['laraneat/author', 'App\\Modules\\Author', realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Author')],
        ])
        ->assertSuccessful();
});

//it('outputs a table of vendor modules', function () {
//    $this->mock(ModulesRepository::class, function(MockInterface $mock) {
//        $mock->shouldReceive('buildVendorModulesManifest')->once();
//    });
//    $this->artisan('module:list')
//        ->expectsOutputToContain('Vendor modules cached!')
//        ->assertSuccessful();
//});
//
//it('outputs a table of all modules', function () {
//    $this->mock(ModulesRepository::class, function(MockInterface $mock) {
//        $mock->shouldReceive('buildVendorModulesManifest')->once();
//    });
//    $this->artisan('module:list')
//        ->expectsOutputToContain('Vendor modules cached!')
//        ->assertSuccessful();
//});
