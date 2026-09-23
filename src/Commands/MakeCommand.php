<?php

declare(strict_types=1);

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Laraneat\Modules\Exceptions\InvalidTemplate;
use Laraneat\Modules\Exceptions\ModulesException;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Scaffold\ApplicationComposer;
use Laraneat\Modules\Scaffold\ComposerRunner;
use Laraneat\Modules\Scaffold\Names;
use Laraneat\Modules\Scaffold\TemplateRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'module:make', description: 'Create a module')]
final class MakeCommand extends Command
{
    protected $signature = 'module:make
        {name : The module name, e.g. "blog" or "shop-order"}
        {--preset=default : The template in "stubs/module/<preset>" of the application}
        {--no-update : Do not run "composer update" for the module}';

    public function handle(
        ModuleRepository $modules,
        TemplateRenderer $renderer,
        ApplicationComposer $composer,
        ComposerRunner $runner,
        Filesystem $files,
    ): int {
        try {
            $name = Names::module($this->argument('name'));
            $package = Names::package(Config::string('modules.vendor'), $name);
            $path = $modules->modulesPath().'/'.$name;

            if (file_exists($path) || is_link($path)) {
                $this->components->error("The directory [{$composer->relative($path)}] already exists.");

                return self::FAILURE;
            }

            $rendered = $renderer->render($this->template($preset = $this->option('preset') ?? 'default'), [
                'name' => $name,
                'namespace' => Names::namespace(Config::string('modules.namespace'), $name),
                'package' => $package,
                'vendor' => explode('/', $package)[0],
                'date' => Date::now()->format('Y_m_d_His'),
            ]);

            if (! isset($rendered['composer.json'])) {
                throw InvalidTemplate::at($preset, 'the template must contain a "composer.json" or "composer.json.stub" file.');
            }

            $this->write($files, $path, $rendered);

            try {
                $modules->refresh();
                $module = $modules->get($name);

                if ($module->package !== $package) {
                    throw InvalidTemplate::at('composer.json', "the package name must be [{$package}], [{$module->package}] given.");
                }

                $root = $composer->composerJson();
                $composer->addModule($root, $module, $modules->modulesPath());
                $root->save();
            } catch (Throwable $exception) {
                $files->deleteDirectory($path);
                $modules->refresh();

                throw $exception;
            }
        } catch (ModulesException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Module [{$name}] created in [{$composer->relative($path)}].");

        if ($this->option('no-update')) {
            $this->components->warn('Install it with: '.$runner->commandLine('update', [$package]));

            return self::SUCCESS;
        }

        if (! $runner->update([$package], $this->streamOutput(...))) {
            $this->components->error('Composer failed to install the module. Fix the problem and run: '.$runner->commandLine('update', [$package]));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function template(string $preset): string
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $preset) !== 1) {
            throw InvalidTemplate::at($preset, 'the preset name may contain lowercase latin letters, digits, "-" and "_".');
        }

        if (is_dir($template = $this->laravel->basePath('stubs/module/'.$preset))) {
            return $template;
        }

        if ($preset === 'default') {
            return dirname(__DIR__, 2).'/resources/stubs/module/default';
        }

        throw InvalidTemplate::at("stubs/module/{$preset}", 'the preset does not exist.');
    }

    /**
     * @param  array<string, string>  $rendered
     */
    private function write(Filesystem $files, string $path, array $rendered): void
    {
        try {
            foreach ($rendered as $file => $contents) {
                $files->ensureDirectoryExists(dirname($path.'/'.$file));
                $files->put($path.'/'.$file, $contents);
            }
        } catch (Throwable $exception) {
            $files->deleteDirectory($path);

            throw $exception;
        }
    }

    private function streamOutput(string $type, string $line): void
    {
        $this->output->write($line);
    }
}
