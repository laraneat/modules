<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use function Laravel\Prompts\multiselect;

class ModelPruneCommand extends PruneCommand implements PromptsForMissingInput
{
    const ALL = 'All';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:prune
                            {module* : Module name(s)}
                            {--all : Check all Modules}
                            {--model=* : Class names of the models to be pruned}
                            {--except=* : Class names of the models to be excluded from pruning}
                            {--path=* : Absolute path(s) to directories where models are located}
                            {--chunk=1000 : The number of models to retrieve per chunk of models to be deleted}
                            {--pretend : Display the number of prunable records found instead of deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune models by module that are no longer needed';

    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        /** @var FileRepository $modulesRepository */
        $modulesRepository = $this->laravel['modules'];

        if ($this->option('all')) {
            $input->setArgument('module', [self::ALL]);
            return;
        }

        $selectedModules = multiselect(
            label   : 'What Module want to check?',
            options : [
                self::ALL,
                ...array_keys($modulesRepository->all()),
            ],
            required: 'You must select at least one module',
        );

        $input->setArgument('module',
            value: in_array(self::ALL, $selectedModules)
                ? [self::ALL]
                : $selectedModules
        );
    }


    /**
     * Determine the models that should be pruned.
     *
     * @return Collection
     */
    protected function models(): Collection
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models)->filter(function ($model) {
                return class_exists($model);
            })->values();
        }

        $except = $this->option('except');

        if (! empty($models) && ! empty($except)) {
            throw new InvalidArgumentException('The --models and --except options cannot be combined.');
        }

        $path = in_array(self::ALL, $this->argument('module'))
            ? sprintf('%s/*/%s',
                GeneratorHelper::path(),
                GeneratorHelper::component('model')->getPath()
            )
            : sprintf('%s/{%s}/%s',
                GeneratorHelper::path(),
                collect($this->argument('module'))->implode(','),
                GeneratorHelper::component('model')->getPath()
            );

        return collect(Finder::create()->in($path)->files()->name('*.php'))
            ->map(function ($model) {

                $namespace = GeneratorHelper::namespace();
                return $namespace . str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        Str::after($model->getRealPath(), realpath(GeneratorHelper::path()))
                    );
            })->values()
            ->when(! empty($except), function ($models) use ($except) {
                return $models->reject(function ($model) use ($except) {
                    return in_array($model, $except);
                });
            })->filter(function ($model) {
                return class_exists($model);
            })->filter(function ($model) {
                return $this->isPrunable($model);
            })->values();
    }

}
