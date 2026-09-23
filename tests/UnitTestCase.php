<?php

declare(strict_types=1);

namespace Laraneat\Modules\Tests;

use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    protected string $directory;

    private string $workingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = TemporaryDirectory::create();
        // Relative paths (and mutants that make paths relative) never reach the repository.
        $this->workingDirectory = (string) getcwd();
        chdir($this->directory);
    }

    protected function tearDown(): void
    {
        chdir($this->workingDirectory);
        TemporaryDirectory::delete($this->directory);

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $files  Contents by path relative to the directory.
     */
    protected function files(array $files): string
    {
        foreach ($files as $path => $contents) {
            is_dir($directory = dirname($this->directory.'/'.$path)) || mkdir($directory, 0777, true);
            file_put_contents($this->directory.'/'.$path, $contents);
        }

        return $this->directory;
    }
}
