<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laraneat\Modules\Facades\Modules;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Tests\TemporaryDirectory;

it('caches the manifest with paths relative to the application', function () {
    $this->artisan('module:cache')->expectsOutputToContain('Modules cached successfully.')->assertSuccessful();

    $cache = require $this->path('bootstrap/cache/modules.php');

    expect(array_keys($cache))->toBe(['blog', 'shop-order'])
        ->and($cache['blog']['path'])->toBe('modules/blog')
        ->and(file_get_contents($this->path('bootstrap/cache/modules.php')))->not->toContain($this->basePath);
});

it('clears the cache', function () {
    $this->artisan('module:cache');

    $this->artisan('module:clear')->expectsOutputToContain('Module cache cleared successfully.')->assertSuccessful();
    $this->artisan('module:clear')->assertSuccessful();

    expect(file_exists($this->path('bootstrap/cache/modules.php')))->toBeFalse();
});

it('boots from the cache without scanning the modules', function () {
    $this->artisan('module:cache');
    $this->files(['modules/wiki/composer.json' => '{"name": "app/wiki", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}']);
    $this->reboot();

    expect(array_keys(Modules::all()))->toBe(['blog', 'shop-order'])
        ->and(config('blog.title'))->toBe('Blog')
        ->and(Route::has('blog.posts'))->toBeTrue();
});

it('skips the files of a module deleted since the cache was written', function () {
    $this->artisan('module:cache');
    TemporaryDirectory::delete($this->path('modules/blog'));
    $this->reboot();

    expect(config('blog'))->toBeNull()
        ->and(Route::has('blog.posts'))->toBeFalse()
        ->and(Route::has('shop-order.orders'))->toBeTrue();

    $this->artisan('module:clear')->assertSuccessful();
});

it('works after the application moved', function () {
    $this->artisan('module:cache');
    $moved = $this->temporaryDirectory($this->basePath);
    TemporaryDirectory::delete($this->basePath);
    $this->basePath = $moved;
    $this->reboot();

    expect(app(ModuleRepository::class)->isCached())->toBeTrue()
        ->and(Modules::get('blog')->path)->toBe($moved.'/modules/blog')
        ->and(config('blog.title'))->toBe('Blog');

    $this->get('/blog')->assertOk()->assertSee('Welcome to the blog');
});

it('keeps absolute paths outside of the application', function () {
    $modules = $this->temporaryDirectory($this->path('modules'));
    $this->files(['config/modules.php' => '<?php return ["path" => "'.$modules.'"];']);
    $this->reboot();

    $this->artisan('module:cache');

    expect((require $this->path('bootstrap/cache/modules.php'))['blog']['path'])->toBe($modules.'/blog');

    $this->reboot();

    expect(Modules::get('blog')->path)->toBe($modules.'/blog');
});
