<?php

declare(strict_types=1);

use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Modules\Blog\Console\Commands\Nested\ArchivePostsCommand;
use Modules\Blog\Console\Commands\PublishPostsCommand;
use Symfony\Component\Console\Application;

it('registers the commands of the modules, including subdirectories', function () {
    $this->artisan('blog:publish')->expectsOutputToContain('blog:publish done')->assertSuccessful();
    $this->artisan('blog:archive')->expectsOutputToContain('blog:archive done')->assertSuccessful();

    expect(Artisan::all())
        ->toHaveKey('blog:publish')
        ->toHaveKey('blog:archive')
        ->and(Artisan::all()['blog:publish'])->toBeInstanceOf(PublishPostsCommand::class)
        ->and(Artisan::all()['blog:archive'])->toBeInstanceOf(ArchivePostsCommand::class);
});

it('skips abstract classes and files that are not commands', function () {
    $commands = collect(Artisan::all())->map(static fn (object $command): string => $command::class)->values();

    expect($commands->filter(static fn (string $class): bool => str_starts_with($class, 'Modules\\'))->sort()->values()->all())
        ->toBe([ArchivePostsCommand::class, PublishPostsCommand::class]);
});

it('registers the commands lazily', function () {
    $this->artisan('module:list')->assertSuccessful();

    $console = (new ReflectionProperty(Kernel::class, 'artisan'))->getValue(app(Illuminate\Contracts\Console\Kernel::class));
    $loader = (new ReflectionProperty(Application::class, 'commandLoader'))->getValue($console);

    expect($loader->has('blog:publish'))->toBeTrue()
        ->and((new ReflectionProperty(Application::class, 'commands'))->getValue($console))
        ->not->toHaveKey('blog:publish');
});

it('uses the "make:command" generator namespace', function () {
    $this->files([
        'config/modules.php' => '<?php return ["generators" => ["make:command" => "Commands"]];',
        'modules/blog/src/Commands/SyncPostsCommand.php' => <<<'PHP'
            <?php

            namespace Modules\Blog\Commands;

            use Illuminate\Console\Command;

            class SyncPostsCommand extends Command
            {
                protected $signature = 'blog:sync';

                public function handle(): void
                {
                    $this->info('synced');
                }
            }
            PHP,
    ]);
    $this->reboot();

    $this->artisan('blog:sync')->expectsOutputToContain('synced')->assertSuccessful();

    expect(Artisan::all())->not->toHaveKey('blog:publish');
});
