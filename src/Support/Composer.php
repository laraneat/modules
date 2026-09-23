<?php

namespace Laraneat\Modules\Support;

use Closure;
use Illuminate\Support\Composer as BaseComposer;
use Laraneat\Modules\Exceptions\ComposerException;
use Symfony\Component\Console\Output\OutputInterface;

class Composer extends BaseComposer
{
    /**
     * Composer's own package name format (see composer.schema.json).
     */
    public const PACKAGE_NAME_REGEX = '{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}D';

    public static function isValidPackageName(string $packageName): bool
    {
        return (bool) preg_match(self::PACKAGE_NAME_REGEX, $packageName);
    }

    /**
     * Update the given Composer packages into the application.
     *
     * @param array<int, string> $packages
     * @param bool $dev
     * @param Closure|OutputInterface|null  $output
     * @param string|null $composerBinary
     * @return bool
     *
     * @throws ComposerException
     */
    public function updatePackages(
        array $packages,
        bool $dev = false,
        Closure|OutputInterface|null $output = null,
        ?string $composerBinary = null
    ): bool {
        return $this->runPackagesCommand('update', $packages, $dev, $output, $composerBinary);
    }

    /**
     * Remove the given Composer packages from the application.
     *
     * @param array<int, string> $packages
     * @param bool $dev
     * @param Closure|OutputInterface|null  $output
     * @param string|null $composerBinary
     * @return bool
     *
     * @throws ComposerException
     */
    public function removePackages(
        array $packages,
        bool $dev = false,
        Closure|OutputInterface|null $output = null,
        $composerBinary = null
    ): bool {
        return $this->runPackagesCommand('remove', $packages, $dev, $output, $composerBinary);
    }

    /**
     * @param array<int, string> $packages
     *
     * @throws ComposerException
     */
    protected function runPackagesCommand(
        string $action,
        array $packages,
        bool $dev,
        Closure|OutputInterface|null $output,
        ?string $composerBinary
    ): bool {
        foreach ($packages as $package) {
            if (! static::isValidPackageName($package)) {
                throw ComposerException::make("Invalid composer package name [{$package}].");
            }
        }

        $command = [
            ...$this->findComposer($composerBinary),
            $action,
            ...($dev ? ['--dev'] : []),
            // Everything after "--" is an argument, so a package name can never be read as an option.
            '--',
            ...$packages,
        ];

        return 0 === $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
                ->run(
                    $output instanceof OutputInterface
                        ? function ($type, $line) use ($output) {
                            $output->write('    '.$line);
                        } : $output
                );
    }
}
