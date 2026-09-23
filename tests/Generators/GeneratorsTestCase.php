<?php

declare(strict_types=1);

namespace Laraneat\Modules\Tests\Generators;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Laraneat\Modules\Tests\TestCase;
use Lorisleiva\Actions\ActionServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Generators of packages (make:action, make:data) get "--module" like the framework ones.
 */
abstract class GeneratorsTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), ActionServiceProvider::class, LaravelDataServiceProvider::class];
    }

    /**
     * Run a generator and return the files it created, with migration dates as "{date}".
     *
     * @return array<string, string> Contents by path relative to the application.
     */
    protected function generate(string $command): array
    {
        $before = $this->applicationFiles();

        expect(Artisan::call($command))->toBe(0, Artisan::output());

        $files = [];

        foreach (array_diff($this->applicationFiles(), $before) as $path) {
            $files[(string) preg_replace('/\d{4}_\d{2}_\d{2}_\d{6}_/', '{date}_', $path)] = (string) file_get_contents($this->path($path));
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function applicationFiles(): array
    {
        return collect(File::allFiles($this->basePath, true))
            ->map(static fn (SplFileInfo $file): string => str_replace('\\', '/', $file->getRelativePathname()))
            ->reject(static fn (string $path): bool => str_starts_with($path, 'vendor/'))
            ->values()
            ->all();
    }
}
