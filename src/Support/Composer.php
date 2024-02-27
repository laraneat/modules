<?php

namespace Laraneat\Modules\Support;

use Closure;
use Illuminate\Support\Composer as BaseComposer;
use Symfony\Component\Console\Output\OutputInterface;

class Composer extends BaseComposer
{
    /**
     * Update the given Composer packages into the application.
     *
     * @param array<int, string> $packages
     * @param bool $dev
     * @param Closure|OutputInterface|null  $output
     * @param string|null $composerBinary
     * @return bool
     */
    public function updatePackages(
        array $packages,
        bool $dev = false,
        Closure|OutputInterface $output = null,
        ?string $composerBinary = null
    ): bool {
        $command = collect([
            ...$this->findComposer($composerBinary),
            'update',
            ...$packages,
        ])
            ->when($dev, function ($command) {
                $command->push('--dev');
            })->all();

        return 0 === $this->getProcess($command, ['COMPOSER_MEMORY_LIMIT' => '-1'])
                ->run(
                    $output instanceof OutputInterface
                        ? function ($type, $line) use ($output) {
                            $output->write('    '.$line);
                        } : $output
                );
    }
}
