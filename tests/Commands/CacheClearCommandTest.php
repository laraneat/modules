<?php

use Laraneat\Modules\ModulesRepository;
use Mockery\MockInterface;

it('clears modules cache', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('pruneModulesManifest')->once();
    });
    $this->artisan('module:clear')
        ->expectsOutputToContain('Modules manifest cache cleared!')
        ->assertSuccessful();
});
