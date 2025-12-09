<?php

namespace Laraneat\Modules\Support;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Laraneat\Modules\Module;
use Laraneat\Modules\ModulesRepository;

class ModuleConfigWriter
{
    public function __construct(
        protected ModulesRepository $modulesRepository,
    ) {
    }

    /**
     * Update module providers in composer.json
     *
     * @param Module $module
     * @param array<int, class-string> $providers
     *
     * @throws FileNotFoundException
     */
    public function updateProviders(Module $module, array $providers): void
    {
        $this->updateConfig($module, 'extra.laravel.providers', $providers);
    }

    /**
     * Update module aliases in composer.json
     *
     * @param Module $module
     * @param array<string, class-string> $aliases
     *
     * @throws FileNotFoundException
     */
    public function updateAliases(Module $module, array $aliases): void
    {
        $this->updateConfig($module, 'extra.laravel.aliases', $aliases);
    }

    /**
     * Add a provider to the module
     *
     * @param Module $module
     * @param class-string $providerClass
     *
     * @throws FileNotFoundException
     */
    public function addProvider(Module $module, string $providerClass): void
    {
        $providers = $module->getProviders();

        if (!in_array($providerClass, $providers, true)) {
            $providers[] = $providerClass;
            $this->updateProviders($module, $providers);
        }
    }

    /**
     * Add an alias to the module
     *
     * @param Module $module
     * @param string $alias
     * @param class-string $class
     *
     * @throws FileNotFoundException
     */
    public function addAlias(Module $module, string $alias, string $class): void
    {
        $aliases = $module->getAliases();

        if (!isset($aliases[$alias]) || $aliases[$alias] !== $class) {
            $aliases[$alias] = $class;
            $this->updateAliases($module, $aliases);
        }
    }

    /**
     * Update a config value in module's composer.json
     *
     * @param Module $module
     * @param string $key
     * @param mixed $value
     *
     * @throws FileNotFoundException|\Exception
     */
    protected function updateConfig(Module $module, string $key, mixed $value): void
    {
        ComposerJsonFile::create($module->getPath() . '/composer.json')
            ->set($key, $value)
            ->save();

        $this->modulesRepository->pruneModulesManifest();
    }
}
