<?php

declare(strict_types=1);

namespace Laraneat\Modules\Generators;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;
use Illuminate\Support\Str;
use Override;

/**
 * The factory of a module model is created where the factory resolver of the module
 * looks for it, and the HasFactory annotation of the model names it.
 *
 * @internal
 */
final class ModelMakeCommand extends BaseModelMakeCommand
{
    use ResolvesModule;

    #[Override]
    protected function createFactory()
    {
        $module = $this->currentModule();

        if ($module === null) {
            parent::createFactory();

            return;
        }

        $this->call('make:factory', [
            'name' => Str::after($this->factoryClass(), $module->namespace.'\\Database\\Factories\\'),
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function buildFactoryReplacements()
    {
        $replacements = parent::buildFactoryReplacements();

        if ($this->currentModule() !== null && isset($replacements['{{ factory }}'])) {
            $factory = $this->factoryClass();

            $replacements['{{ factory }}'] = (string) preg_replace_callback(
                '/HasFactory<[^>]*>/',
                static fn (): string => 'HasFactory<\\'.$factory.'>',
                $replacements['{{ factory }}'],
            );
        }

        return $replacements;
    }

    private function factoryClass(): string
    {
        // @phpstan-ignore argument.type, argument.templateType (the model is being generated, it does not exist yet)
        return Factory::resolveFactoryName($this->qualifyClass($this->getNameInput()));
    }
}
