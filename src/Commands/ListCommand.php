<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Module;

class ListCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:list
                            {--o|only= : Types of modules will be displayed (enabled/disabled)}
                            {--d|direction=asc : The direction of ordering (asc/desc)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->twoColumnDetail('<fg=gray>Status / Name</>', '<fg=gray>Path / priority</>');
        collect($this->getModules())->each(function (Module $module) {
            $row = [
                $module->isEnabled() ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                $module->getName(),
                $module->getPath(),
                $module->getPriority(),
            ];
            $this->components->twoColumnDetail("[{$row[0]}] {$row[1]}", "{$row[2]} [{$row[3]}]");
        });

        return self::SUCCESS;
    }

    /**
     * @return array<string, Module>
     */
    protected function getModules(): array
    {
        /** @var string $only */
        $only = $this->option('only');

        return match ($only) {
            'enabled' => $this->modules->getByStatus(true),
            'disabled' => $this->modules->getByStatus(false),
            default => $this->modules->all(),
        };
    }
}
