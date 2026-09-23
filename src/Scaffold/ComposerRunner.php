<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

use Closure;
use Illuminate\Support\Facades\Process;

use function Illuminate\Support\php_binary;

/**
 * Runs Composer in the application directory. Package names are validated and passed
 * after "--", so they can never be read as options.
 *
 * @internal
 */
final readonly class ComposerRunner
{
    public function __construct(
        private string $basePath,
    ) {}

    /**
     * @param  list<string>  $packages
     * @param  (Closure(string, string): void)|null  $output
     */
    public function update(array $packages, ?Closure $output = null): bool
    {
        return $this->run('update', $packages, $output);
    }

    /**
     * @param  list<string>  $packages
     * @param  (Closure(string, string): void)|null  $output
     */
    public function remove(array $packages, ?Closure $output = null): bool
    {
        return $this->run('remove', $packages, $output);
    }

    /**
     * The command line to run by hand.
     *
     * @param  list<string>  $packages
     */
    public function commandLine(string $action, array $packages): string
    {
        return implode(' ', ['composer', $action, ...array_map(Names::assertPackage(...), $packages)]);
    }

    /**
     * @param  list<string>  $packages
     * @return list<string>
     */
    public function command(string $action, array $packages): array
    {
        $composer = is_file($this->basePath.'/composer.phar')
            ? [php_binary(), 'composer.phar']
            : ['composer'];

        return [...$composer, $action, '--', ...array_map(Names::assertPackage(...), $packages)];
    }

    /**
     * @param  list<string>  $packages
     * @param  (Closure(string, string): void)|null  $output
     */
    private function run(string $action, array $packages, ?Closure $output): bool
    {
        return Process::path($this->basePath)
            ->env(['COMPOSER_MEMORY_LIMIT' => '-1'])
            ->forever()
            ->run($this->command($action, $packages), $output)
            ->successful();
    }
}
