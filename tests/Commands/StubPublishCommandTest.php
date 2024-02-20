<?php

/**
* @return array<int, string>
 */
function getFilePathsInDirectory(string $directory): array
{
    $filesystem = new Illuminate\Filesystem\Filesystem();
    return array_map(fn (SplFileInfo $fileInfo) => $fileInfo->getRealPath(), $filesystem->allFiles($directory));
}

beforeEach(function () {
    $this->customStubsPath = $this->app['config']->get('modules.generator.custom_stubs');
});

afterEach(function () {
    $this->filesystem->deleteDirectory($this->customStubsPath);
});

it('publish all laraneat/modules stubs', function () {
    $this->artisan('module:stub:publish')
        ->assertSuccessful();

    expect(getFilePathsInDirectory($this->customStubsPath))->toMatchSnapshot();
});
