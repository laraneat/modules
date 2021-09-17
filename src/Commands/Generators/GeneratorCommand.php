<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Console\Command;
use Laraneat\Modules\Exceptions\FileAlreadyExistException;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Generators\FileGenerator;
use Laraneat\Modules\Module;

abstract class GeneratorCommand extends Command
{
    /**
     * The name of 'name' argument.
     *
     * @var string
     */
    protected string $argumentName = '';

    /**
     * Get template contents.
     *
     * @return string
     */
    abstract protected function getTemplateContents();

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getDestinationFilePath();

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = str_replace('\\', '/', $this->getDestinationFilePath());

        if (!$this->laravel['files']->isDirectory($dir = dirname($path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getTemplateContents();

        try {
            $overwriteFile = $this->hasOption('force') ? $this->option('force') : false;
            (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->error("File : {$path} already exists.");

            return E_ERROR;
        }

        return 0;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return class_basename($this->argument($this->argumentName));
    }

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return '';
    }

    /**
     * Get class namespace.
     *
     * @param Module $module
     *
     * @return string
     */
    public function getClassNamespace(Module $module): string
    {
        $extra = str_replace(
            array($this->getClass(), '/'),
            array('', '\\'),
            $this->argument($this->argumentName)
        );

        $namespace = Modules::config('namespace');

        $namespace .= '\\' . $module->getStudlyName();

        $namespace .= '\\' . $this->getDefaultNamespace();

        $namespace .= '\\' . $extra;

        $namespace = str_replace('/', '\\', $namespace);

        return trim($namespace, '\\');
    }
}
