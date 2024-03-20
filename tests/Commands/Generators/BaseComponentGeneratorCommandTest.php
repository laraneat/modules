<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Commands\Generators\BaseComponentGeneratorCommand;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\ModulesRepository;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/../../fixtures/stubs/modules/valid/article',
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
        __DIR__ . '/../../fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/../../fixtures/stubs/modules/valid/empty',
        __DIR__ . '/../../fixtures/stubs/modules/valid/navigation',
    ], $this->app->basePath('/modules'));
});

describe('getFullClassFromOptionOrAsk()', function () {
    class GetFullClassFromOptionOrAskTestCommand extends BaseComponentGeneratorCommand
    {
        protected $signature = 'test:command {module?} {--model=}';

        public function handle(): int
        {
            $module = $this->getModuleArgumentOrFail();
            $modelClass = $this->getFullClassFromOptionOrAsk(
                optionName: 'model',
                question: 'Enter "model" class name',
                componentType: ModuleComponentType::Model,
                module: $module
            );
            $this->line($modelClass);

            return self::SUCCESS;
        }
    }

    beforeEach(function () {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new GetFullClassFromOptionOrAskTestCommand(
            $this->app[ModulesRepository::class],
            $this->app[Filesystem::class],
        ));
    });


    it('returns the full class of component if only the base class name is specified', function () {
        $this->artisan('test:command', [
            'module' => 'navigation',
            '--model' => 'SomeGeoLocationModel',
        ])
            ->expectsOutput('Modules\\GeoLocation\\Models\\SomeGeoLocationModel');

        $this->artisan('test:command', [
            'module' => 'navigation',
            '--model' => 'Some\\GeoLocationModel',
        ])
            ->expectsOutput('Modules\\GeoLocation\\Models\\Some\\GeoLocationModel');

        $this->artisan('test:command', [
            'module' => 'navigation',
        ])
            ->expectsQuestion('Enter "model" class name', 'SomeGeoLocationModel')
            ->expectsOutput('Modules\\GeoLocation\\Models\\SomeGeoLocationModel');

        $this->artisan('test:command', [
            'module' => 'navigation',
        ])
            ->expectsQuestion('Enter "model" class name', 'Some\\GeoLocationModel')
            ->expectsOutput('Modules\\GeoLocation\\Models\\Some\\GeoLocationModel');
    });

    it('returns the specified class if it begins with a backslash', function () {
        $this->artisan('test:command', [
            'module' => 'navigation',
        ])
            ->expectsQuestion('Enter "model" class name', '\\Modules\\Article\\Models\\Article')
            ->expectsOutput('\\Modules\\Article\\Models\\Article');

        $this->artisan('test:command', [
            'module' => 'navigation',
            '--model' => '\\Modules\\Article\\Models\\Article',
        ])
            ->expectsOutput('\\Modules\\Article\\Models\\Article');

        $this->artisan('test:command', [
            'module' => 'navigation',
        ])
            ->expectsQuestion('Enter "model" class name', '\\Modules\\GeoLocation\\Models\\GeoLocation')
            ->expectsOutput('\\Modules\\GeoLocation\\Models\\GeoLocation');

        $this->artisan('test:command', [
            'module' => 'navigation',
            '--model' => '\\Modules\\GeoLocation\\Models\\GeoLocation',
        ])
            ->expectsOutput('\\Modules\\GeoLocation\\Models\\GeoLocation');
    });
});
