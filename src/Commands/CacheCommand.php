<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\ModuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'module:cache', description: 'Create a cache file of the module manifest')]
final class CacheCommand extends Command
{
    protected $signature = 'module:cache';

    public function handle(ModuleRepository $modules): int
    {
        $bootstrap = $this->laravel->bootstrapPath('app.php');

        // This process may have booted from the config cache of the previous deploy, which "optimize"
        // has replaced by now. Like "route:cache", read the route groups from a fresh application.
        if ($this->laravel instanceof CachesConfiguration && $this->laravel->configurationIsCached() && is_file($bootstrap)) {
            $this->freshRepository($bootstrap)->cache();
            $modules->forget();
        } else {
            $modules->cache();
        }

        $this->components->info('Modules cached successfully.');

        return self::SUCCESS;
    }

    private function freshRepository(string $bootstrap): ModuleRepository
    {
        try {
            /** @var Application $app */
            $app = require $bootstrap;

            $app->make(Kernel::class)->bootstrap();

            return $app->make(ModuleRepository::class);
        } finally {
            // A new application replaces this one as the container and facade instance.
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->laravel);
            Container::setInstance($this->laravel);
        }
    }
}
