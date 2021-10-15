<?php

namespace Laraneat\Modules\Migrations;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class Migrator
{
    /**
     * Module instance.
     */
    protected Module $module;

    /**
     * Laravel Application instance.
     */
    protected Application $laravel;

    /**
     * The database connection to be used
     */
    protected string $database = '';

    public function __construct(Module $module, Application $application)
    {
        $this->module = $module;
        $this->laravel = $application;
    }

    /**
     * Set the database connection to be used
     */
    public function setDatabase(string $database): static
    {
        if ($database) {
            $this->database = $database;
        }

        return $this;
    }

    /**
     * Get module instance.
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Get migration path.
     */
    public function getPath(): string
    {
        $config = $this->module->get('migration');

        $migrationPath = GeneratorHelper::component('migration');
        $path = (is_array($config) && array_key_exists('path', $config)) ? $config['path'] : $migrationPath->getPath();

        return $this->module->getExtraPath($path);
    }

    /**
     * Get migration files.
     */
    public function getMigrations(bool $reverse = false): array
    {
        $files = $this->laravel['files']->glob($this->getPath() . '/*_*.php');

        // Once we have the array of files in the directory we will just remove the
        // extension and take the basename of the file which is all we need when
        // finding the migrations that haven't been run against the databases.
        if ($files === false) {
            return [];
        }

        $files = array_map(static function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        if ($reverse) {
            return array_reverse($files);
        }

        return $files;
    }

    /**
     * Rollback migration.
     */
    public function rollback(): array
    {
        $migrations = $this->getLast($this->getMigrations(true));

        $this->requireFiles($migrations->toArray());

        $migrated = [];

        foreach ($migrations as $migration) {
            $data = $this->find($migration);

            if ($data->count()) {
                $migrated[] = $migration;

                $this->down($migration);

                $data->delete();
            }
        }

        return $migrated;
    }

    /**
     * Reset migration.
     */
    public function reset(): array
    {
        $migrations = $this->getMigrations(true);

        $this->requireFiles($migrations);

        $migrated = [];

        foreach ($migrations as $migration) {
            $data = $this->find($migration);

            if ($data->count()) {
                $migrated[] = $migration;

                $this->down($migration);

                $data->delete();
            }
        }

        return $migrated;
    }

    /**
     * Run down schema from the given migration name.
     */
    public function down(string $migration): void
    {
        $this->resolve($migration)->down();
    }

    /**
     * Run up schema from the given migration name.
     */
    public function up(string $migration): void
    {
        $this->resolve($migration)->up();
    }

    /**
     * Resolve a migration instance from a file.
     */
    public function resolve(string $file): object
    {
        $file = implode('_', array_slice(explode('_', $file), 4));

        $class = Str::studly($file);

        return new $class();
    }

    /**
     * Require in all the migration files in a given path.
     */
    public function requireFiles(array $files): void
    {
        $path = $this->getPath();
        foreach ($files as $file) {
            $this->laravel['files']->requireOnce($path . '/' . $file . '.php');
        }
    }

    /**
     * Get table instance.
     */
    public function table(): Builder
    {
        return $this->laravel['db']->connection($this->database ?: null)->table(config('database.migrations'));
    }

    /**
     * Find migration data from database by given migration name.
     */
    public function find(string $migration): Builder
    {
        return $this->table()->where('migration', $migration);
    }

    /**
     * Save new migration to database.
     */
    public function log(string $migration): bool
    {
        return $this->table()->insert([
            'migration' => $migration,
            'batch' => $this->getNextBatchNumber(),
        ]);
    }

    /**
     * Get the next migration batch number.
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     */
    public function getLastBatchNumber(?array $migrations = null): int
    {
        $table = $this->table();

        if (is_array($migrations)) {
            $table = $table->whereIn('migration', $migrations);
        }

        return $table->max('batch');
    }

    /**
     * Get the last migration batch.
     */
    public function getLast(array $migrations): Collection
    {
        $query = $this->table()
            ->where('batch', $this->getLastBatchNumber($migrations))
            ->whereIn('migration', $migrations);

        $result = $query->orderBy('migration', 'desc')->get();

        return collect($result)->map(function ($item) {
            return (array) $item;
        })->pluck('migration');
    }

    /**
     * Get the ran migrations.
     */
    public function getRan(): Collection
    {
        return $this->table()->pluck('migration');
    }
}
