<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Commands\Generators\BaseComponentGeneratorCommand;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\ModulesRepository;

beforeEach(function () {
    $this->setAppModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/app/Author'),
    ], $this->app->basePath('/app/Modules'));
    $this->setVendorModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
    ]);
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
        // app
        $this->artisan('test:command', [
            'module' => 'Author',
            '--model' => 'SomeAuthorModel',
        ])
            ->expectsOutput('App\\Modules\\Author\\Models\\SomeAuthorModel');

        $this->artisan('test:command', [
            'module' => 'Author',
            '--model' => 'Some\\AuthorModel',
        ])
            ->expectsOutput('App\\Modules\\Author\\Models\\Some\\AuthorModel');

        $this->artisan('test:command', [
            'module' => 'Author',
        ])
            ->expectsQuestion('Enter "model" class name', 'SomeAuthorModel')
            ->expectsOutput('App\\Modules\\Author\\Models\\SomeAuthorModel');

        $this->artisan('test:command', [
            'module' => 'Author',
        ])
            ->expectsQuestion('Enter "model" class name', 'Some\\AuthorModel')
            ->expectsOutput('App\\Modules\\Author\\Models\\Some\\AuthorModel');

        // vendor
        $this->artisan('test:command', [
            'module' => 'foo',
            '--model' => 'SomeAuthorModel',
        ])
            ->expectsOutput('Laraneat\\Foo\\Models\\SomeAuthorModel');

        $this->artisan('test:command', [
            'module' => 'foo',
            '--model' => 'Some\\AuthorModel',
        ])
            ->expectsOutput('Laraneat\\Foo\\Models\\Some\\AuthorModel');

        $this->artisan('test:command', [
            'module' => 'foo',
        ])
            ->expectsQuestion('Enter "model" class name', 'Some\\AuthorModel')
            ->expectsOutput('Laraneat\\Foo\\Models\\Some\\AuthorModel');
    });

    it('returns the specified class if it begins with a backslash', function () {
        // app
        $this->artisan('test:command', [
            'module' => 'Author',
        ])
            ->expectsQuestion('Enter "model" class name', '\\App\\Modules\\Article\\Models\\Article')
            ->expectsOutput('\\App\\Modules\\Article\\Models\\Article');

        $this->artisan('test:command', [
            'module' => 'Author',
            '--model' => '\\App\\Modules\\Article\\Models\\Article',
        ])
            ->expectsOutput('\\App\\Modules\\Article\\Models\\Article');

        $this->artisan('test:command', [
            'module' => 'Author',
        ])
            ->expectsQuestion('Enter "model" class name', '\\App\\Modules\\Author\\Models\\Author')
            ->expectsOutput('\\App\\Modules\\Author\\Models\\Author');

        $this->artisan('test:command', [
            'module' => 'Author',
            '--model' => '\\App\\Modules\\Author\\Models\\Author',
        ])
            ->expectsOutput('\\App\\Modules\\Author\\Models\\Author');

        // vendor
        $this->artisan('test:command', [
            'module' => 'foo',
        ])
            ->expectsQuestion('Enter "model" class name', '\\App\\Modules\\Author\\Models\\Author')
            ->expectsOutput('\\App\\Modules\\Author\\Models\\Author');

        $this->artisan('test:command', [
            'module' => 'foo',
            '--model' => '\\App\\Modules\\Author\\Models\\Author',
        ])
            ->expectsOutput('\\App\\Modules\\Author\\Models\\Author');
    });
});
