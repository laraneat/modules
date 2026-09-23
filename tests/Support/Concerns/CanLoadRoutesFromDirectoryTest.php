<?php

use Illuminate\Support\Facades\Route;
use Laraneat\Modules\Support\Concerns\CanLoadRoutesFromDirectory;

beforeEach(function () {
    $this->routesDirectory = sys_get_temp_dir() . '/laraneat-routes-' . uniqid();
    @mkdir($this->routesDirectory . '/v1', 0777, true);

    file_put_contents($this->routesDirectory . '/list_articles.php', "<?php\nIlluminate\\Support\\Facades\\Route::get('articles', fn () => 'ok')->name('articles.list');\n");
    file_put_contents($this->routesDirectory . '/v1/view_article.php', "<?php\nIlluminate\\Support\\Facades\\Route::get('articles/{id}', fn () => 'ok')->name('articles.view');\n");
    file_put_contents($this->routesDirectory . '/list_articles.php.orig', "<?php\nIlluminate\\Support\\Facades\\Route::get('articles-old', fn () => 'ok')->name('articles.old');\n");
    file_put_contents($this->routesDirectory . '/README.md', 'This text must never be echoed.');
});

afterEach(function () {
    $this->app['files']->deleteDirectory($this->routesDirectory);
});

it('loads route files and prefixes nested directories', function () {
    $loader = new class () {
        use CanLoadRoutesFromDirectory;

        public function load(string $directory): void
        {
            $this->loadRoutesFromDirectory($directory);
        }
    };

    ob_start();
    Route::prefix('api')->group(fn () => $loader->load($this->routesDirectory));
    $output = ob_get_clean();

    $routes = Route::getRoutes();
    $routes->refreshNameLookups();

    expect($output)->toBe('')
        ->and($routes->getByName('articles.list')?->uri())->toBe('api/articles')
        ->and($routes->getByName('articles.view')?->uri())->toBe('api/v1/articles/{id}')
        ->and($routes->getByName('articles.old'))->toBeNull();
});
