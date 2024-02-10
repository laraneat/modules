<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Database\Console\ShowModelCommand;
use Illuminate\Support\Str;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('module:model:show', 'Show information about an Eloquent model in modules')]
class ModelShowCommand extends ShowModelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:model:show {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about an Eloquent model in modules';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:model:show
                            {model : The model to show}
                            {--database= : The database connection to use}
                            {--json : Output the model as JSON}';

    /**
     * Qualify the given model class base name.
     *
     * @param string $model
     * @return string
     *
     * @see \Illuminate\Console\GeneratorCommand
     */
    protected function qualifyModel(string $model): string
    {
        if (str_contains($model, '\\') && class_exists($model)) {
            return $model;
        }

        $modelPaths = glob(implode(DIRECTORY_SEPARATOR, [
            GeneratorHelper::path(),
            '*',
            GeneratorHelper::component('model')->getPath(),
            "$model.php"
        ]));

        if ($modelPaths && isset($modelPaths[0])) {
            $modelPath = $modelPaths[0];
            return str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::replaceFirst(GeneratorHelper::path(), GeneratorHelper::namespace(), $modelPath)
            );
        }

        return $model;
    }

}
