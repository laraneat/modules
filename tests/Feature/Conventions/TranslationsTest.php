<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Translation\TranslationServiceProvider;
use Laraneat\Modules\ModuleRepository;
use Laraneat\Modules\Registrars\ResourceRegistrar;

it('registers the translations of the modules under their names', function () {
    expect(__('blog::messages.welcome'))->toBe('Welcome to the blog')
        ->and(__('shop-order::orders.created'))->toBe('Order created');
});

it('registers the JSON translations of the modules that have them', function () {
    expect(__('Read more'))->toBe('Read the whole post')
        ->and(app('translation.loader')->jsonPaths())->toBe([$this->path('modules/blog/lang')])
        ->and(app('translation.loader')->namespaces())->toMatchArray([
            'blog' => $this->path('modules/blog/lang'),
            'shop-order' => $this->path('modules/shop-order/lang'),
        ]);
});

it('lets the application override the module translations', function () {
    $this->files(['lang/vendor/blog/en/messages.php' => '<?php return ["welcome" => "Hello"];']);

    expect(__('blog::messages.welcome'))->toBe('Hello');
});

it('adds the translations whether the translator is resolved before or after the modules boot', function (bool $before) {
    $manifest = app(ModuleRepository::class)->manifest();
    $app = new Application($this->path());
    Container::setInstance($this->app);
    $app->instance('files', new Filesystem);
    $app->instance('config', new Repository(['app' => ['locale' => 'en', 'fallback_locale' => 'en']]));
    (new TranslationServiceProvider($app))->register();

    $translator = $before ? $app->make('translator') : null;

    (new ResourceRegistrar($app, $manifest))->register();

    $translator ??= $app->make('translator');

    expect($translator->get('blog::messages.welcome'))->toBe('Welcome to the blog')
        ->and($translator->get('Read more'))->toBe('Read the whole post');
})->with(['resolved before' => true, 'resolved after' => false]);
