<?php

namespace Laraneat\Modules\Activators;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Module;

class FileActivator implements ActivatorInterface
{
    /**
     * Laravel cache instance
     *
     * @var CacheManager
     */
    protected CacheManager $cache;

    /**
     * Laravel Filesystem instance
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Laravel config instance
     *
     * @var Config
     */
    protected Config $config;

    /**
     * @var string
     */
    protected string $cacheKey;

    /**
     * @var string
     */
    protected string $cacheLifetime;

    /**
     * Array of modules activation statuses
     *
     * @var array
     */
    protected array $modulesStatuses;

    /**
     * File used to store activation statuses
     *
     * @var string
     */
    protected string $statusesFile;

    public function __construct(Container $app)
    {
        $this->cache = $app['cache'];
        $this->files = $app['files'];
        $this->config = $app['config'];
        $this->statusesFile = $this->config('statuses-file');
        $this->cacheKey = $this->config('cache-key');
        $this->cacheLifetime = $this->config('cache-lifetime');
        $this->modulesStatuses = $this->getModulesStatuses();
    }

    /**
     * Get the path of the file where statuses are stored
     *
     * @return string
     */
    public function getStatusesFilePath(): string
    {
        return $this->statusesFile;
    }

    /**
     * @inheritDoc
     */
    public function reset(): void
    {
        if ($this->files->exists($this->statusesFile)) {
            $this->files->delete($this->statusesFile);
        }
        $this->modulesStatuses = [];
        $this->flushCache();
    }

    /**
     * @inheritDoc
     */
    public function enable(Module $module): void
    {
        $this->setActiveByName($module->getName(), true);
    }

    /**
     * @inheritDoc
     */
    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    /**
     * @inheritDoc
     */
    public function hasStatus(Module $module, bool $status): bool
    {
        if (!isset($this->modulesStatuses[$module->getName()])) {
            return $status === false;
        }

        return $this->modulesStatuses[$module->getName()] === $status;
    }

    /**
     * @inheritDoc
     */
    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    /**
     * @inheritDoc
     */
    public function setActiveByName(string $moduleName, bool $active): void
    {
        $this->modulesStatuses[$moduleName] = $active;
        $this->writeJson();
        $this->flushCache();
    }

    /**
     * @inheritDoc
     */
    public function delete(Module $module): void
    {
        if (!isset($this->modulesStatuses[$module->getName()])) {
            return;
        }
        unset($this->modulesStatuses[$module->getName()]);
        $this->writeJson();
        $this->flushCache();
    }

    /**
     * Writes the activation statuses in a file, as json
     */
    protected function writeJson(): void
    {
        $this->files->put($this->statusesFile, json_encode($this->modulesStatuses, JSON_PRETTY_PRINT));
    }

    /**
     * Reads the json file that contains the activation statuses.
     *
     * @return array
     */
    protected function readJson(): array
    {
        if (!$this->files->exists($this->statusesFile)) {
            return [];
        }

        return json_decode($this->files->get($this->statusesFile), true);
    }

    /**
     * Get modules statuses, either from the cache or from
     * the json statuses file if the cache is disabled.
     *
     * @return array
     */
    protected function getModulesStatuses(): array
    {
        if (!$this->config->get('modules.cache.enabled')) {
            return $this->readJson();
        }

        return $this->cache->remember($this->cacheKey, $this->cacheLifetime, function () {
            return $this->readJson();
        });
    }

    /**
     * Reads a config parameter under the 'activators.file' key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        return $this->config->get('modules.activators.file.' . $key, $default);
    }

    /**
     * Flush the modules activation statuses cache
     */
    public function flushCache(): void
    {
        $this->cache->forget($this->cacheKey);
    }
}
