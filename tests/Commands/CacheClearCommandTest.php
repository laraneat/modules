<?php

use Laraneat\Modules\ModulesRepository;
use Mockery\MockInterface;

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

it('clears vendor modules cache', function () {
    $this->mock(ModulesRepository::class, function(MockInterface $mock) {
        $mock->shouldReceive('pruneVendorModulesManifest')->once();
    });
    $this->artisan('module:clear --vendor')
        ->expectsOutputToContain('Vendor modules cache cleared!')
        ->assertSuccessful();
});

it('clears app modules cache', function () {
    $this->mock(ModulesRepository::class, function(MockInterface $mock) {
        $mock->shouldReceive('pruneAppModulesManifest')->once();
    });
    $this->artisan('module:clear --app')
        ->expectsOutputToContain('App modules cache cleared!')
        ->assertSuccessful();
});

it('clears app and vendor modules cache', function () {
    $this->mock(ModulesRepository::class, function(MockInterface $mock) {
        $mock->shouldReceive('pruneVendorModulesManifest')->once();
        $mock->shouldReceive('pruneAppModulesManifest')->once();
    });
    $this->artisan('module:clear')
        ->expectsOutputToContain('Vendor modules cache cleared!')
        ->expectsOutputToContain('App modules cache cleared!')
        ->assertSuccessful();
});
