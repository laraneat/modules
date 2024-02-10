<?php

namespace Laraneat\Modules\Commands;

use Laraneat\Modules\Json;
use Laraneat\Modules\Process\Installer;

class InstallCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:install
                            {name? : The name of module will be installed}
                            {version?=latest : The version of module will be installed}
                            {--timeout= : The process timeout}
                            {--path= : The installation path}
                            {--type=composer : The type of installation}
                            {--tree : Install the module as a git subtree}
                            {--no-update : Disables the automatic update of the dependencies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the specified module by given package name (vendor/name).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (is_null($this->argument('name'))) {
            return $this->installFromFile();
        }

        $this->install(
            $this->argument('name'),
            $this->argument('version'),
            $this->option('type'),
            $this->option('tree')
        );

        return self::SUCCESS;
    }

    /**
     * Install modules from modules.json file.
     */
    protected function installFromFile(): int
    {
        if (!file_exists($path = base_path('modules.json'))) {
            $this->error("File 'modules.json' does not exist in your project root.");

            return E_ERROR;
        }

        $modules = Json::make($path);

        $dependencies = $modules->get('require', []);

        /** @var array<string, mixed> $module */
        foreach ($dependencies as $module) {
            $module = collect($module);

            $this->install(
                $module->get('name'),
                $module->get('version'),
                $module->get('type')
            );
        }

        return self::SUCCESS;
    }

    /**
     * Install the specified module.
     *
     * @param string $name
     * @param string $version
     * @param string $type
     * @param bool   $tree
     */
    protected function install(string $name, string $version = 'latest', string $type = 'composer', bool $tree = false): void
    {
        $installer = new Installer(
            $name,
            $version,
            $type ?: $this->option('type'),
            $tree ?: $this->option('tree')
        );

        $installer->setRepository($this->modules);

        $installer->setConsole($this);

        if ($timeout = (int) $this->option('timeout')) {
            $installer->setTimeout($timeout);
        }

        if ($path = $this->option('path')) {
            $installer->setPath($path);
        }

        $installer->run();

        if (!$this->option('no-update')) {
            $this->call('module:update', [
                'module' => $installer->getModuleName(),
            ]);
        }
    }
}
