<?php

use Laraneat\Modules\ModulesRepository;
use Mockery\MockInterface;

it('caches vendor modules', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('buildVendorModulesManifest')->once();
    });
    $this->artisan('module:cache --vendor')
        ->expectsOutputToContain('Vendor modules cached!')
        ->assertSuccessful();
});

it('caches app modules', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('buildAppModulesManifest')->once();
    });
    $this->artisan('module:cache --app')
        ->expectsOutputToContain('App modules cached!')
        ->assertSuccessful();
});

it('caches app and vendor modules', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('buildVendorModulesManifest')->once();
        $mock->shouldReceive('buildAppModulesManifest')->once();
    });

    $this->artisan('module:cache')
        ->expectsOutputToContain('Vendor modules cached!')
        ->expectsOutputToContain('App modules cached!')
        ->assertSuccessful();
});
