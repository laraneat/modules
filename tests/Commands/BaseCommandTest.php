<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Laraneat\Modules\Commands\BaseCommand;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\ModulesRepository;
use Symfony\Component\Console\Exception\InvalidOptionException;

beforeEach(function() {
    $this->setAppModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Article'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Author'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/app/Foo'),
    ], $this->app->basePath('/app/Modules'));
    $this->setVendorModules([
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);
});

describe('single "module" argument', function () {
    class CommandWithSingleModuleArgument extends BaseCommand
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'single-module-argument-command {module? : Module name}';

        /**
         * Execute the console command.
         */
        public function handle(): int
        {
            try {
                $moduleToHandle = $this->getModuleArgumentOrFail();
                $this->line($moduleToHandle->getPackageName());
            } catch (ModuleNotFoundException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function() {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithSingleModuleArgument($this->app[ModulesRepository::class]));
    });

    it('can accept a package name as a single "module" argument', function() {
        $this->artisan('single-module-argument-command laraneat/article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command app/foo')
            ->expectsOutput('app/foo')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/foo')
            ->expectsOutput('laraneat/foo')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/bar')
            ->expectsOutput('laraneat/bar')
            ->assertSuccessful();
    });

    it('can accept a case-insensitive module name as a single "module" argument', function() {
        $this->artisan('single-module-argument-command Article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command ARTICLE')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command ArTiCLE')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command Author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command Bar')
            ->expectsOutput('laraneat/bar')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command bar')
            ->expectsOutput('laraneat/bar')
            ->assertSuccessful();
    });

    it('displays an error message when passing an invalid single "module" argument', function() {
        $this->artisan('single-module-argument-command laraneat/articlee')
            ->expectsOutput("Module with 'laraneat/articlee' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('single-module-argument-command laraneat')
            ->expectsOutput("Module with 'laraneat' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('single-module-argument-command laraneat/article/')
            ->expectsOutput("Module with 'laraneat/article/' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('single-module-argument-command /article')
            ->expectsOutput("Module with '/article' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('single-module-argument-command articlee')
            ->expectsOutput("Module with 'articlee' name or package name does not exist!")
            ->assertFailed();
    });

    it('gives a module selection if the "module" argument is not passed', function() {
        $this->artisan('single-module-argument-command')
            ->expectsChoice(
                question: 'Select one module',
                answer: 'laraneat/author',
                answers: [
                    'laraneat/article' => 'laraneat/article',
                    'laraneat/author' => 'laraneat/author',
                    'app/foo' => 'app/foo',
                    'laraneat/foo' => 'laraneat/foo',
                    'laraneat/bar' => 'laraneat/bar'
                ]
            )
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();
    });

    it('gives a module selection if 2 or more modules with the same names are found', function() {
        $expectedChoiceOptions = [
            'app/foo' => 'app/foo',
            'laraneat/foo' => 'laraneat/foo'
        ];

        $this->artisan('single-module-argument-command Foo')
            ->expectsChoice(
                question: "2 modules with name «Foo» found, please select one module from those found",
                answer: 'app/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('app/foo')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command foo')
            ->expectsChoice(
                question: "2 modules with name «foo» found, please select one module from those found",
                answer: 'laraneat/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/foo')
            ->assertSuccessful();
    });
});

describe('multiple "module" argument', function () {
    class CommandWithMultipleModuleArgument extends BaseCommand
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'multiple-module-argument-command {module?* : Module name(s) or package name(s)}';

        /**
         * Execute the console command.
         */
        public function handle(): int
        {
            try {
                $modulesToHandle = $this->getModuleArgumentOrFail();
                $this->line(
                    collect($modulesToHandle)
                        ->map(fn (\Laraneat\Modules\Module $module) => $module->getPackageName())
                        ->join(', ')
                );
            } catch (ModuleNotFoundException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function() {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithMultipleModuleArgument($this->app[ModulesRepository::class]));
    });

    it('can accept a package name as a multiple "module" argument', function() {
        $this->artisan('multiple-module-argument-command laraneat/article laraneat/author')
            ->expectsOutput('laraneat/article, laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/article laraneat/article laraneat/author')
            ->expectsOutput('laraneat/article, laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author laraneat/author laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/bar app/foo app/foo laraneat/foo laraneat/bar')
            ->expectsOutput('laraneat/bar, app/foo, laraneat/foo')
            ->assertSuccessful();
    });

    it('can accept "all" as a multiple "module" argument', function() {
        $this->artisan('multiple-module-argument-command all')
            ->expectsOutput('laraneat/foo, laraneat/bar, laraneat/article, laraneat/author, app/foo')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command all laraneat/bar laraneat/author')
            ->expectsOutput('laraneat/foo, laraneat/bar, laraneat/article, laraneat/author, app/foo')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/bar all laraneat/author')
            ->expectsOutput('laraneat/foo, laraneat/bar, laraneat/article, laraneat/author, app/foo')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author laraneat/bar all')
            ->expectsOutput('laraneat/foo, laraneat/bar, laraneat/article, laraneat/author, app/foo')
            ->assertSuccessful();
    });

    it('can accept a case-insensitive module name as a multiple "module" argument', function() {
        $this->artisan('multiple-module-argument-command Article BAR author Author arTicLe Bar')
            ->expectsOutput('laraneat/article, laraneat/bar, laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command ARTICLE bar')
            ->expectsOutput('laraneat/article, laraneat/bar')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command ARTICLE article Article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();
    });

    it('can accept a case-insensitive module name and package name as a multiple "module" argument', function() {
        $this->artisan('multiple-module-argument-command Article BAR author app/foo Author arTicLe Bar')
            ->expectsOutput('laraneat/article, laraneat/bar, laraneat/author, app/foo')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/article ARTICLE bar')
            ->expectsOutput('laraneat/article, laraneat/bar')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command ARTICLE article Article laraneat/article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();
    });

    it('displays an error message when passing an invalid multiple "module" argument', function() {
        $this->artisan('multiple-module-argument-command laraneat/foo laraneat/articlee laraneat/barr')
            ->expectsOutput("Module with 'laraneat/articlee' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command laraneat laraneat/article')
            ->expectsOutput("Module with 'laraneat' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command Bar laraneat/article/')
            ->expectsOutput("Module with 'laraneat/article/' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command article /article')
            ->expectsOutput("Module with '/article' name or package name does not exist!")
            ->assertFailed();
    });

    it('gives a module selection if the multiple "module" argument is not passed', function() {
        $choices = collect([
            'None' => '',
            'all' => 'All modules',
            'laraneat/article' => 'laraneat/article',
            'laraneat/author' => 'laraneat/author',
            'app/foo' => 'app/foo',
            'laraneat/foo' => 'laraneat/foo',
            'laraneat/bar' => 'laraneat/bar'
        ]);

        $this->artisan('multiple-module-argument-command')
            ->expectsChoice(
                question: 'Select one or more module',
                answer: ['laraneat/author', 'app/foo', 'laraneat/bar'],
                answers: collect()
                    ->merge($choices->keys())
                    ->merge($choices->values())
                    ->sort()
                    ->all()
            )
            ->expectsOutput('laraneat/author, app/foo, laraneat/bar')
            ->assertSuccessful();
    });

    it('gives a module selection if 2 or more modules with the same names are found', function() {
        $expectedChoiceOptions = [
            'app/foo' => 'app/foo',
            'laraneat/foo' => 'laraneat/foo'
        ];

        $this->artisan('multiple-module-argument-command foo laraneat/article Author Foo')
            ->expectsChoice(
                question: "2 modules with name «foo» found, please select one module from those found",
                answer: 'laraneat/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsChoice(
                question: "2 modules with name «Foo» found, please select one module from those found",
                answer: 'laraneat/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/foo, laraneat/article, laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command foo laraneat/article Author Foo')
            ->expectsChoice(
                question: "2 modules with name «foo» found, please select one module from those found",
                answer: 'laraneat/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsChoice(
                question: "2 modules with name «Foo» found, please select one module from those found",
                answer: 'app/foo',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/foo, laraneat/article, laraneat/author, app/foo')
            ->assertSuccessful();
    });
});

describe('getOptionOrAsk', function() {
    class CommandWithOptionAsking extends BaseCommand
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'command-with-option-asking {--foo=}';

        /**
         * Execute the console command.
         */
        public function handle(): int
        {
            try {
                $this->line($this->getOptionOrAsk('foo', 'Enter "foo" option'));
            } catch (InvalidOptionException $exception) {
                $this->line($exception->getMessage());
                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function() {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithOptionAsking($this->app[ModulesRepository::class]));
    });

    it('asks for the option value if it is not specified', function() {
        $this->artisan('command-with-option-asking')
            ->expectsQuestion(
                question: 'Enter "foo" option',
                answer: 'some foo value',
            )
            ->expectsOutput('some foo value')
            ->assertSuccessful();

        $this->artisan('command-with-option-asking --foo=')
            ->expectsQuestion(
                question: 'Enter "foo" option',
                answer: 'some foo value 2',
            )
            ->expectsOutput('some foo value 2')
            ->assertSuccessful();

        $this->artisan('command-with-option-asking')
            ->expectsQuestion(
                question: 'Enter "foo" option',
                answer: '',
            )
            ->expectsOutput('The «foo» option is required')
            ->assertFailed();
    });

    it('does not ask for an option value if it specified', function() {
        $this->artisan('command-with-option-asking --foo=some-foo-value')
            ->expectsOutput('some-foo-value')
            ->assertSuccessful();
    });
});

describe('getOptionOrChoice', function() {
    class CommandWithOptionChoice extends BaseCommand
    {
        public const CHOICES = ['first', 'second', 'third'];

        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'command-with-option-choice {--foo=}';

        /**
         * Execute the console command.
         */
        public function handle(): int
        {
            try {
                $this->line($this->getOptionOrChoice('foo', 'Choice the "foo" option', self::CHOICES));
            } catch (InvalidOptionException $exception) {
                $this->line($exception->getMessage());
                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function() {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithOptionChoice($this->app[ModulesRepository::class]));
    });

    it('let you choose an option value if it is not specified', function() {
        $this->artisan('command-with-option-choice')
            ->expectsChoice(
                question: 'Choice the "foo" option',
                answer: 'first',
                answers: CommandWithOptionChoice::CHOICES
            )
            ->expectsOutput('first')
            ->assertSuccessful();

        $this->artisan('command-with-option-choice --foo=')
            ->expectsChoice(
                question: 'Choice the "foo" option',
                answer: 'second',
                answers: CommandWithOptionChoice::CHOICES
            )
            ->expectsOutput('second')
            ->assertSuccessful();
    });

    it('doesnt let you choose an option value if it specified', function() {
        $this->artisan('command-with-option-choice --foo=first')
            ->expectsOutput('first')
            ->assertSuccessful();
    });

    it('shows an error if the passed option value is not valid', function() {
        $this->artisan('command-with-option-choice --foo=some-invalid-value')
            ->expectsOutput('Wrong «foo» option value provided. Value should be one of «first» or «second» or «third».')
            ->assertFailed();
    });
});
