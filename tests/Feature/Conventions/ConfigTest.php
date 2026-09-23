<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Laraneat\Modules\Exceptions\InvalidModule;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Registrars\ResourceRegistrar;

it('merges the config files of the modules', function () {
    expect(config('blog'))->toBe(['title' => 'Blog', 'per_page' => 15])
        ->and(config('shop-order.currency'))->toBe('EUR');
});

it('lets the application config override the module config', function () {
    $this->files(['config/blog.php' => '<?php return ["per_page" => 50, "author" => "admin"];']);
    $this->reboot();

    expect(config('blog'))->toBe(['title' => 'Blog', 'per_page' => 50, 'author' => 'admin']);
});

it('merges the config before the providers boot', function () {
    $this->app->register(new class($this->app) extends ServiceProvider
    {
        public ?string $title = null;

        public function boot(): void
        {
            $this->title = config('blog.title');
        }
    });

    expect(collect($this->app->getProviders(ServiceProvider::class))->last()->title)->toBe('Blog');
});

it('does not read the module config when the config is cached', function () {
    // Testbench always loads the config files, so the registrar is run by hand.
    $this->app->instance('config_loaded_from_cache', true);
    config(['blog' => null]);

    (new ResourceRegistrar($this->app, app(ModuleRepository::class)->manifest()))->registerConfig();

    expect($this->app->configurationIsCached())->toBeTrue()
        ->and(config('blog'))->toBeNull();
});

it('refuses a config file that does not return an array', function () {
    $this->files(['modules/blog/config/broken.php' => '<?php return "broken";']);

    expect(fn () => $this->reboot())->toThrow(InvalidModule::class, 'config/broken.php must return an array.');
});
