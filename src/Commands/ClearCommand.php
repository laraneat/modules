<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\ModuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:clear', description: 'Remove the module manifest cache file')]
final class ClearCommand extends Command
{
    protected $signature = 'module:clear';

    public function handle(ModuleRepository $modules): int
    {
        $modules->clearCache();

        $this->components->info('Module cache cleared successfully.');

        return self::SUCCESS;
    }
}
