<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\ModuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:cache', description: 'Create a cache file of the module manifest')]
final class CacheCommand extends Command
{
    protected $signature = 'module:cache';

    public function handle(ModuleRepository $modules): int
    {
        $modules->cache();

        $this->components->info('Modules cached successfully.');

        return self::SUCCESS;
    }
}
