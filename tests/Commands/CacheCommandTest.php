<?php

use Laraneat\Modules\ModulesRepository;
use Mockery\MockInterface;

it('caches modules', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('buildModulesManifest')->once();
    });
    $this->artisan('module:cache')
        ->expectsOutputToContain('Modules manifest cached!')
        ->assertSuccessful();
});
