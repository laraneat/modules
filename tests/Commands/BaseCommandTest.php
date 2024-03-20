<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Laraneat\Modules\Commands\BaseCommand;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\ModulesRepository;
use Symfony\Component\Console\Exception\InvalidOptionException;

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/../fixtures/stubs/modules/valid/article',
        __DIR__ . '/../fixtures/stubs/modules/valid/author',
        __DIR__ . '/../fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/../fixtures/stubs/modules/valid/empty',
        __DIR__ . '/../fixtures/stubs/modules/valid/navigation',
    ], $this->app->basePath('/modules'));
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
            } catch (ModuleNotFound $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function () {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithSingleModuleArgument($this->app[ModulesRepository::class]));
    });

    it('can accept a package name as a single "module" argument', function () {
        $this->artisan('single-module-argument-command laraneat/article-category')
            ->expectsOutput('laraneat/article-category')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/empty')
            ->expectsOutput('laraneat/empty')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command empty/empty')
            ->expectsOutput('empty/empty')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command laraneat/location')
            ->expectsOutput('laraneat/location')
            ->assertSuccessful();
    });

    it('can accept a module name as a single "module" argument', function () {
        $this->artisan('single-module-argument-command Article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command article')
            ->expectsOutput('laraneat/article')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command article-category')
            ->expectsOutput('laraneat/article-category')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command ArticleCategory')
            ->expectsOutput('laraneat/article-category')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command location')
            ->expectsOutput('laraneat/location')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command Location')
            ->expectsOutput('laraneat/location')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command navigation')
            ->expectsOutput('laraneat/location')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command Navigation')
            ->expectsOutput('laraneat/location')
            ->assertSuccessful();
    });

    it('displays an error message when passing an invalid single "module" argument', function () {
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

    it('gives a module selection if the "module" argument is not passed', function () {
        $this->artisan('single-module-argument-command')
            ->expectsChoice(
                question: 'Select one module',
                answer: 'laraneat/empty',
                answers: [
                    'laraneat/article-category',
                    'laraneat/article',
                    'laraneat/author',
                    'laraneat/empty',
                    'empty/empty',
                    'laraneat/location',
                ]
            )
            ->expectsOutput('laraneat/empty')
            ->assertSuccessful();
    });

    it('gives a module selection if 2 or more modules with the same names are found', function () {
        $expectedChoiceOptions = [
            'laraneat/empty',
            'empty/empty',
        ];

        $this->artisan('single-module-argument-command Empty')
            ->expectsChoice(
                question: "2 modules with name 'Empty' found, please select one module from those found",
                answer: 'empty/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('empty/empty')
            ->assertSuccessful();

        $this->artisan('single-module-argument-command empty')
            ->expectsChoice(
                question: "2 modules with name 'empty' found, please select one module from those found",
                answer: 'laraneat/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/empty')
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
            } catch (ModuleNotFound $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }
    }

    beforeEach(function () {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithMultipleModuleArgument($this->app[ModulesRepository::class]));
    });

    it('can accept a package name as a multiple "module" argument', function () {
        $this->artisan('multiple-module-argument-command laraneat/article laraneat/empty empty/empty')
            ->expectsOutput('laraneat/article, laraneat/empty, empty/empty')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/article laraneat/article laraneat/empty empty/empty laraneat/empty')
            ->expectsOutput('laraneat/article, laraneat/empty, empty/empty')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author laraneat/author laraneat/author')
            ->expectsOutput('laraneat/author')
            ->assertSuccessful();
    });

    it('can accept "all" as a multiple "module" argument', function () {
        $this->artisan('multiple-module-argument-command all')
            ->expectsOutput('laraneat/article-category, laraneat/article, laraneat/author, laraneat/empty, empty/empty, laraneat/location')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command all laraneat/empty laraneat/author')
            ->expectsOutput('laraneat/article-category, laraneat/article, laraneat/author, laraneat/empty, empty/empty, laraneat/location')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/empty all laraneat/author')
            ->expectsOutput('laraneat/article-category, laraneat/article, laraneat/author, laraneat/empty, empty/empty, laraneat/location')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command laraneat/author laraneat/empty all')
            ->expectsOutput('laraneat/article-category, laraneat/article, laraneat/author, laraneat/empty, empty/empty, laraneat/location')
            ->assertSuccessful();
    });

    it('can accept a module name as a multiple "module" argument', function () {
        $this->artisan('multiple-module-argument-command Article location Navigation ArticleCategory Location article-category')
            ->expectsOutput('laraneat/article, laraneat/location, laraneat/article-category')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command ArticleCategory articleCategory article-category article_category')
            ->expectsOutput('laraneat/article-category')
            ->assertSuccessful();
    });

    it('can accept a module name and package name as a multiple "module" argument', function () {
        $this->artisan('multiple-module-argument-command Article location Navigation empty/empty ArticleCategory Location article-category')
            ->expectsOutput('laraneat/article, laraneat/location, empty/empty, laraneat/article-category')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command ArticleCategory articleCategory laraneat/article-category article-category article_category')
            ->expectsOutput('laraneat/article-category')
            ->assertSuccessful();
    });

    it('displays an error message when passing an invalid multiple "module" argument', function () {
        $this->artisan('multiple-module-argument-command laraneat/empty laraneat/articlee laraneat/navigation')
            ->expectsOutput("Module with 'laraneat/articlee' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command laraneat laraneat/navigation')
            ->expectsOutput("Module with 'laraneat' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command Author laraneat/navigation')
            ->expectsOutput("Module with 'laraneat/navigation' name or package name does not exist!")
            ->assertFailed();

        $this->artisan('multiple-module-argument-command article /article')
            ->expectsOutput("Module with '/article' name or package name does not exist!")
            ->assertFailed();
    });

    it('gives a module selection if the multiple "module" argument is not passed', function () {
        $choices = collect([
            'None' => '',
            'all' => 'All modules',
            'laraneat/article-category' => 'laraneat/article-category',
            'laraneat/article' => 'laraneat/article',
            'laraneat/author' => 'laraneat/author',
            'laraneat/empty' => 'laraneat/empty',
            'empty/empty' => 'empty/empty',
            'laraneat/location' => 'laraneat/location',
        ]);

        $this->artisan('multiple-module-argument-command')
            ->expectsChoice(
                question: 'Select one or more module',
                answer: ['laraneat/article', 'empty/empty', 'laraneat/location'],
                answers: collect()
                    ->merge($choices->keys())
                    ->merge($choices->values())
                    ->sort()
                    ->all()
            )
            ->expectsOutput('laraneat/article, empty/empty, laraneat/location')
            ->assertSuccessful();
    });

    it('gives a module selection if 2 or more modules with the same names are found', function () {
        $expectedChoiceOptions = [
            'laraneat/empty',
            'empty/empty',
        ];

        $this->artisan('multiple-module-argument-command empty laraneat/article Author Empty')
            ->expectsChoice(
                question: "2 modules with name 'empty' found, please select one module from those found",
                answer: 'laraneat/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsChoice(
                question: "2 modules with name 'Empty' found, please select one module from those found",
                answer: 'laraneat/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/empty, laraneat/article, laraneat/author')
            ->assertSuccessful();

        $this->artisan('multiple-module-argument-command empty laraneat/article Author Empty')
            ->expectsChoice(
                question: "2 modules with name 'empty' found, please select one module from those found",
                answer: 'laraneat/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsChoice(
                question: "2 modules with name 'Empty' found, please select one module from those found",
                answer: 'empty/empty',
                answers: $expectedChoiceOptions
            )
            ->expectsOutput('laraneat/empty, laraneat/article, laraneat/author, empty/empty')
            ->assertSuccessful();
    });
});

describe('getOptionOrAsk', function () {
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

    beforeEach(function () {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithOptionAsking($this->app[ModulesRepository::class]));
    });

    it('asks for the option value if it is not specified', function () {
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
            ->expectsOutput("The 'foo' option is required")
            ->assertFailed();
    });

    it('does not ask for an option value if it specified', function () {
        $this->artisan('command-with-option-asking --foo=some-foo-value')
            ->expectsOutput('some-foo-value')
            ->assertSuccessful();
    });
});

describe('getOptionOrChoice', function () {
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

    beforeEach(function () {
        /** @var Illuminate\Foundation\Console\Kernel $console */
        $console = $this->app[ConsoleKernelContract::class];
        $console->registerCommand(new CommandWithOptionChoice($this->app[ModulesRepository::class]));
    });

    it('let you choose an option value if it is not specified', function () {
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

    it('doesnt let you choose an option value if it specified', function () {
        $this->artisan('command-with-option-choice --foo=first')
            ->expectsOutput('first')
            ->assertSuccessful();
    });

    it('shows an error if the passed option value is not valid', function () {
        $this->artisan('command-with-option-choice --foo=some-invalid-value')
            ->expectsOutput("Wrong 'foo' option value provided. Value should be one of 'first' or 'second' or 'third'.")
            ->assertFailed();
    });
});
