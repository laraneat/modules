<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Module;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:list';

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
        $this->table(['Name', 'Status', 'Priority', 'Path'], $this->getRows());

        return self::SUCCESS;
    }

    /**
     * Get table rows.
     *
     * @return array
     */
    public function getRows(): array
    {
        $rows = [];

        foreach ($this->getModules() as $module) {
            $rows[] = [
                $module->getName(),
                $module->isEnabled() ? 'Enabled' : 'Disabled',
                $module->get('priority'),
                $module->getPath(),
            ];
        }

        return $rows;
    }

    /**
     * @return Module[]
     */
    public function getModules(): array
    {
        switch ($this->option('only')) {
            case 'enabled':
                return Modules::getByStatus(true);

            case 'disabled':
                return Modules::getByStatus(false);

            case 'priority':
                return Modules::getOrdered($this->option('direction'));

            default:
                return Modules::all();
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Types of modules will be displayed.', null],
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
        ];
    }
}
