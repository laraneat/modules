<?php

use function Spatie\Snapshots\assertMatchesSnapshot;

beforeEach(function () {
    $this->customStubsPath = $this->app['config']->get('modules.custom_stubs');
});

afterEach(function () {
    $this->filesystem->deleteDirectory($this->customStubsPath);
});

it('publish all laraneat/modules stubs', function () {
    $this->artisan('module:stub:publish')
        ->assertSuccessful();

    assertMatchesSnapshot(getRelativeFilePathsInDirectory($this->customStubsPath));
});
