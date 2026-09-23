<?php

declare(strict_types=1);

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\NotificationMakeCommand;
use Illuminate\Support\Facades\Artisan;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\Generators\ModuleContext;
use Laraneat\Modules\Generators\ModuleOption;
use Symfony\Component\Console\Input\InputOption;

it('creates a model with its factory, migration, seeder and policy in the module', function () {
    $files = $this->generate('make:model Comment -mfs --policy --module=blog');

    expect(array_keys($files))->toEqualCanonicalizing([
        'modules/blog/src/Models/Comment.php',
        'modules/blog/src/Policies/CommentPolicy.php',
        'modules/blog/database/factories/CommentFactory.php',
        'modules/blog/database/migrations/{date}_create_comments_table.php',
        'modules/blog/database/seeders/CommentSeeder.php',
    ])
        ->and($files['modules/blog/src/Models/Comment.php'])->toContain('/** @use HasFactory<\\Modules\\Blog\\Database\\Factories\\CommentFactory> */')
        ->and($files['modules/blog/database/factories/CommentFactory.php'])->toContain(
            'namespace Modules\\Blog\\Database\\Factories;',
            'use Modules\\Blog\\Models\\Comment;',
            '@extends Factory<Comment>',
        )
        ->and($files['modules/blog/database/seeders/CommentSeeder.php'])->toContain('namespace Modules\\Blog\\Database\\Seeders;')
        ->and($files['modules/blog/src/Policies/CommentPolicy.php'])->toContain('use Modules\\Blog\\Models\\Comment;');
});

it('creates the factory of a nested model where the factory resolver looks for it', function () {
    $files = $this->generate('make:model Admin/Role --factory --module=blog');

    expect(array_keys($files))->toEqualCanonicalizing([
        'modules/blog/src/Models/Admin/Role.php',
        'modules/blog/database/factories/Admin/RoleFactory.php',
    ])
        ->and($files['modules/blog/src/Models/Admin/Role.php'])->toContain('HasFactory<\\Modules\\Blog\\Database\\Factories\\Admin\\RoleFactory>')
        ->and($files['modules/blog/database/factories/Admin/RoleFactory.php'])->toContain(
            'namespace Modules\\Blog\\Database\\Factories\\Admin;',
            'use Modules\\Blog\\Models\\Admin\\Role;',
        );
});

it('registers a module provider in the composer.json of the module', function () {
    $this->artisan('make:provider EventServiceProvider --module=blog')
        ->expectsOutputToContain('Provider [Modules\\Blog\\Providers\\EventServiceProvider] registered in [modules/blog/composer.json]. Run "php artisan module:sync" to install it.')
        ->assertSuccessful();

    $this->artisan('make:provider EventServiceProvider --module=blog')
        ->expectsOutputToContain('Provider already exists.')
        ->doesntExpectOutputToContain('registered in')
        ->assertSuccessful();

    expect($this->readJson('modules/blog/composer.json')['extra'])
        ->toBe(['laravel' => ['providers' => ['Modules\\Blog\\Providers\\EventServiceProvider']]])
        ->and(file_exists($this->path('modules/blog/src/Providers/EventServiceProvider.php')))->toBeTrue()
        ->and(file_exists($this->path('bootstrap/providers.php')))->toBeFalse();
});

it('uses the namespaces of the "generators" config', function (string $command, string $path) {
    $this->files(['config/modules.php' => '<?php return ["generators" => ["make:action" => "UI\\\\Actions\\\\", "make:controller" => "Http\\\\Api"]];']);
    $this->reboot();

    expect(array_keys($this->generate($command)))->toBe([$path]);
})->with([
    'action' => ['make:action PublishPost --module=blog', 'modules/blog/src/UI/Actions/PublishPost.php'],
    'nested name' => ['make:action Posts/PublishPost --module=blog', 'modules/blog/src/UI/Actions/Posts/PublishPost.php'],
    'backslashes' => ['make:action \\\\Posts\\\\PublishPost --module=shop-order', 'modules/shop-order/src/UI/Actions/Posts/PublishPost.php'],
    'fully qualified name' => ['make:action "Modules\\\\Blog\\\\Domain\\\\PublishPost" --module=blog', 'modules/blog/src/Domain/PublishPost.php'],
    'controller' => ['make:controller PostController --module=blog', 'modules/blog/src/Http/Api/PostController.php'],
    'other generators' => ['make:job PublishPost --module=blog', 'modules/blog/src/Jobs/PublishPost.php'],
    'without --module' => ['make:action PublishPost', 'app/Actions/PublishPost.php'],
]);

it('generates into the application without --module', function (string $command, array $paths) {
    expect(array_keys($this->generate($command)))->toEqualCanonicalizing($paths);
})->with([
    'model' => ['make:model Tag -mfs', [
        'app/Tag.php',
        'database/factories/TagFactory.php',
        'database/migrations/{date}_create_tags_table.php',
        'database/seeders/TagSeeder.php',
    ]],
    'test' => ['make:test PostTest --phpunit', ['tests/Feature/PostTest.php']],
    'view' => ['make:view posts.show --test --phpunit', ['resources/views/posts/show.blade.php', 'tests/Feature/View/Posts/ShowTest.php']],
    'component' => ['make:component Alert', ['app/View/Components/Alert.php', 'resources/views/components/alert.blade.php']],
    'provider' => ['make:provider EventServiceProvider', ['app/Providers/EventServiceProvider.php']],
]);

it('names the default markdown view of a notification after the module', function () {
    $files = $this->generate('make:notification PostPublished --markdown --module=blog');

    expect($files['modules/blog/src/Notifications/PostPublished.php'])->toContain("->markdown('blog::mail.post-published')")
        ->and($files)->toHaveKey('modules/blog/resources/views/mail/post-published.blade.php');
})->skip(! method_exists(NotificationMakeCommand::class, 'getView'), 'make:notification names a default markdown view since Laravel 13.33.');

it('keeps application view names without --module', function () {
    $files = $this->generate('make:mail PostPublished --markdown');

    expect($files['app/Mail/PostPublished.php'])->toContain("markdown: 'mail.post-published'");
});

it('points the application at the module while a generator runs', function () {
    $state = static fn (): array => [
        app()->path(),
        app()->databasePath(),
        app()->configPath(),
        app()->getNamespace(),
        config('view.paths'),
        app(ModuleContext::class)->module(),
    ];
    $before = $state();
    $module = Modules::get('blog');

    $during = app(ModuleContext::class)->run($module, $state);

    expect($during)->toBe([
        $this->path('modules/blog/src'),
        $this->path('modules/blog/database'),
        $this->path('modules/blog/config'),
        'Modules\\Blog\\',
        [$this->path('modules/blog/resources/views')],
        $module,
    ])
        ->and($state())->toBe($before)
        ->and($before[3])->toBe('App\\');

    expect(fn () => app(ModuleContext::class)->run($module, static fn () => throw new RuntimeException('Generator failed')))
        ->toThrow(RuntimeException::class, 'Generator failed')
        ->and($state())->toBe($before);

    Artisan::call('make:model Tag -f --module=blog');

    expect($state())->toBe($before);
});

it('fails for a missing module', function () {
    $this->artisan('make:model Tag --module=wiki')
        ->expectsOutputToContain('Module [wiki] not found.')
        ->assertFailed();

    expect(file_exists($this->path('app/Models/Tag.php')))->toBeFalse();
});

it('leaves a generator with its own --module option alone', function () {
    $command = new class(app('files')) extends GeneratorCommand
    {
        protected $name = 'make:own-module';

        protected function getStub(): string
        {
            return __FILE__;
        }

        public function handle(): int
        {
            $this->line('own module: '.$this->option('module'));

            return self::SUCCESS;
        }

        protected function getOptions(): array
        {
            return [['module', null, InputOption::VALUE_REQUIRED, 'Its own option']];
        }
    };

    ModuleOption::addTo($command);
    Artisan::registerCommand($command);

    $this->artisan('make:own-module Thing --module=anything')
        ->expectsOutputToContain('own module: anything')
        ->assertSuccessful();

    expect($command->getDefinition()->getOption('module')->getDescription())->toBe('Its own option');
});
