<?php

use Laraneat\Modules\Exceptions\ComposerException;
use Laraneat\Modules\Exceptions\ModuleHasNoNamespace;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\ModulesRepository;
use Mockery\MockInterface;

it('syncs modules with composer', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('pruneModulesManifest')->once();
        $mock->shouldReceive('syncWithComposer')->once();
    });

    $this->artisan('module:sync')
        ->expectsOutputToContain('Modules completed successfully!')
        ->assertSuccessful();
});

it('handles ModuleHasNoNamespace exception', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('pruneModulesManifest')->once();
        $mock->shouldReceive('syncWithComposer')->once()->andThrow(
            ModuleHasNoNamespace::make('test/module')
        );
    });

    $this->artisan('module:sync')
        ->expectsOutputToContain('No namespace specified for module')
        ->assertSuccessful();
});

it('handles ModuleHasNonUniquePackageName exception', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('pruneModulesManifest')->once();
        $mock->shouldReceive('syncWithComposer')->once()->andThrow(
            ModuleHasNonUniquePackageName::make('test/module', ['/path1', '/path2'])
        );
    });

    $this->artisan('module:sync')
        ->expectsOutputToContain('test/module')
        ->assertSuccessful();
});

it('handles ComposerException and shows manual update hint', function () {
    $this->mock(ModulesRepository::class, function (MockInterface $mock) {
        $mock->shouldReceive('pruneModulesManifest')->once();
        $mock->shouldReceive('syncWithComposer')->once()->andThrow(
            ComposerException::make('Failed to update package with composer.')
        );
        $mock->shouldReceive('getModules')->once()->andReturn([
            'test/module-a' => (object) [],
            'test/module-b' => (object) [],
        ]);
    });

    $this->artisan('module:sync')
        ->expectsOutputToContain('Failed to update package with composer')
        ->expectsOutputToContain('composer update test/module-a test/module-b')
        ->assertSuccessful();
});
