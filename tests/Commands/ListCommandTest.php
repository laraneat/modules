<?php

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

it('outputs a table of modules', function () {
    $this->artisan('module:list')
        ->expectsTable(['Package Name', 'Namespace', 'Path'], [
            ['laraneat/article-category', 'Modules\\ArticleCategory', $this->app->basePath('/modules/article-category')],
            ['laraneat/article', 'Modules\\Article', $this->app->basePath('/modules/article')],
            ['laraneat/author', 'Modules\\Author', $this->app->basePath('/modules/author')],
            ['laraneat/empty', 'Modules\\Empty', $this->app->basePath('/modules/empty-module')],
            ['empty/empty', 'Empty\\Empty', $this->app->basePath('/modules/empty')],
            ['laraneat/location', 'Modules\\GeoLocation', $this->app->basePath('/modules/navigation')],

        ])
        ->assertSuccessful();
});
