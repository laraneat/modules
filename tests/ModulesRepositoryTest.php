<?php

use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\ModulesRepository;
use Laraneat\Modules\Support\Composer;
use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->modulesManifestPath = $this->app->bootstrapPath('cache/testing-laraneat-modules.php');
    $this->repository = new ModulesRepository(
        app: $this->app,
        modulesPath: $this->app['config']->get('modules.path'),
        modulesManifestPath: $this->modulesManifestPath,
    );
});

afterEach(function () {
    $this->filesystem->delete($this->modulesManifestPath);
});

describe('scan paths', function () {
    it('sets the initial scan paths from the configuration', function () {
        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/modules/*'),
        ]);
    });

    it('can add one scan path', function () {
        $this->repository->addScanPath($this->app->basePath('/foo_bar'));
        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/modules/*'),
            $this->app->basePath('/foo_bar/*'),
        ]);
    });

    it('can add array of scan paths', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/foo/bar/test folder'),
            $this->app->basePath('/foo/test/'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/modules/*'),
            $this->app->basePath('/foo/bar/test folder/*'),
            $this->app->basePath('/foo/test/*'),
        ]);
    });

    it('normalizes the added paths', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/foo/bar/some1////'),
            $this->app->basePath('/foo/bar/some2'),
            $this->app->basePath('/foo/bar/some3/*'),
            $this->app->basePath('/foo/bar/some4/*/'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/modules/*'),
            $this->app->basePath('/foo/bar/some1/*'),
            $this->app->basePath('/foo/bar/some2/*'),
            $this->app->basePath('/foo/bar/some3/*'),
            $this->app->basePath('/foo/bar/some4/*'),
        ]);
    });

    it('rejects adding a path that is already in the scan paths', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/modules'),
            $this->app->basePath('/modules/'),
            $this->app->basePath('/modules////'),
            $this->app->basePath('/modules/*'),
            $this->app->basePath('/modules/Nested'),
            $this->app->basePath('/modules/Nested/'),
            $this->app->basePath('/modules/Nested////'),
            $this->app->basePath('/modules/Nested/*'),
            $this->app->basePath('/foo/test/'),
            $this->app->basePath('/foo/test////'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/modules/*'),
            $this->app->basePath('/modules/Nested/*'),
            $this->app->basePath('/foo/test/*'),
        ]);
    });
});

describe('modules manifest', function () {
    it('can build modules manifest', function () {
        $this->setModules([
            __DIR__ . '/fixtures/stubs/modules/valid/article',
            __DIR__ . '/fixtures/stubs/modules/valid/article-category',
            __DIR__ . '/fixtures/stubs/modules/valid/author',
            __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
            __DIR__ . '/fixtures/stubs/modules/valid/navigation',
        ]);

        $expectedManifest = [
            'laraneat/article-category' => [
                'path' => $this->app->basePath('/modules/article-category'),
                'name' => 'article-category',
                'namespace' => 'Modules\\ArticleCategory\\',
                'providers' => [
                    'Modules\\ArticleCategory\\Providers\\ArticleCategoryServiceProvider',
                    'Modules\\ArticleCategory\\Providers\\RouteServiceProvider',
                ],
                'aliases' => [],
            ],
            'laraneat/article' => [
            'path' => $this->app->basePath('/modules/article'),
                'name' => 'article',
                'namespace' => 'Modules\\Article\\',
                'providers' => [
                    'Modules\\Article\\Providers\\ArticleServiceProvider',
                    'Modules\\Article\\Providers\\RouteServiceProvider',
                ],
                'aliases' => [],
            ],
            'laraneat/author' => [
                'path' => $this->app->basePath('/modules/author'),
                'name' => 'author',
                'namespace' => 'Modules\\Author\\',
                'providers' => [
                    'Modules\\Author\\Providers\\AuthorServiceProvider',
                    'Modules\\Author\\Providers\\RouteServiceProvider',
                ],
                'aliases' => [
                    'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
                ],
            ],
            'laraneat/empty' => [
                'path' => $this->app->basePath('/modules/empty-module'),
                'name' => 'empty-module',
                'namespace' => 'Modules\\Empty\\',
                'providers' => [],
                'aliases' => [],
            ],
            'laraneat/location' => [
                'path' => $this->app->basePath('/modules/navigation'),
                'name' => 'navigation',
                'namespace' => 'Modules\\GeoLocation\\',
                'providers' => [
                    'Modules\\GeoLocation\\Providers\\GeoLocationServiceProvider',
                    'Modules\\GeoLocation\\Providers\\RouteServiceProvider'
                ],
                'aliases' => [],
            ],
        ];

        expect($this->repository->buildModulesManifest())
            ->toBe($expectedManifest)
            ->and($this->modulesManifestPath)->toBeFile();
    });

    it('throws an exception when modules have the same package names', function () {
        $this->setModules([
            __DIR__ . '/fixtures/stubs/modules/valid/article',
            __DIR__ . '/fixtures/stubs/modules/valid/article-copy',
        ]);

        $this->repository->buildModulesManifest();
    })->throws(ModuleHasNonUniquePackageName::class);

    it('can prune modules manifest', function () {
        $this->setModules([
            __DIR__ . '/fixtures/stubs/modules/valid/article',
            __DIR__ . '/fixtures/stubs/modules/valid/article-category',
            __DIR__ . '/fixtures/stubs/modules/valid/author',
            __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
            __DIR__ . '/fixtures/stubs/modules/valid/navigation',
        ]);

        $this->repository->buildModulesManifest();
        expect($this->modulesManifestPath)->toBeFile();
        $this->repository->pruneModulesManifest();
        expect($this->modulesManifestPath)->not->toBeFile();
    });
});

it('can return modules as array', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    expect($this->repository->toArray())->toBe([
        'laraneat/article-category' => [
            'path' => $this->app->basePath('/modules/article-category'),
            'packageName' => 'laraneat/article-category',
            'name' => 'article-category',
            'namespace' => 'Modules\\ArticleCategory',
            'providers' => [
                'Modules\\ArticleCategory\\Providers\\ArticleCategoryServiceProvider',
                'Modules\\ArticleCategory\\Providers\\RouteServiceProvider',
            ],
            'aliases' => []
        ],
        'laraneat/article' => [
            'path' => $this->app->basePath('/modules/article'),
            'packageName' => 'laraneat/article',
            'name' => 'article',
            'namespace' => 'Modules\\Article',
            'providers' => [
                'Modules\\Article\\Providers\\ArticleServiceProvider',
                'Modules\\Article\\Providers\\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/author' => [
            'path' => $this->app->basePath('/modules/author'),
            'packageName' => 'laraneat/author',
            'name' => 'author',
            'namespace' => 'Modules\\Author',
            'providers' => [
                'Modules\\Author\\Providers\\AuthorServiceProvider',
                'Modules\\Author\\Providers\\RouteServiceProvider',
            ],
            'aliases' => [
                'AuthorFacade' => 'Modules\\Author\\Facades\\SomeFacade',
            ]
        ],
        'laraneat/empty' => [
            'path' => $this->app->basePath('/modules/empty-module'),
            'packageName' => 'laraneat/empty',
            'name' => 'empty-module',
            'namespace' => 'Modules\\Empty',
            'providers' => [],
            'aliases' => []
        ],
        'laraneat/location' => [
            'path' => $this->app->basePath('/modules/navigation'),
            'packageName' => 'laraneat/location',
            'name' => 'navigation',
            'namespace' => 'Modules\\GeoLocation',
            'providers' => [
                'Modules\\GeoLocation\\Providers\\GeoLocationServiceProvider',
                'Modules\\GeoLocation\\Providers\\RouteServiceProvider'
            ],
            'aliases' => []
        ],
    ]);
});

it('can check the module for existence', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    expect($this->repository->has('laraneat/foo'))->toBe(false)
        ->and($this->repository->has('laraneat/article'))->toBe(true)
        ->and($this->repository->has('laraneat/author'))->toBe(true)
        ->and($this->repository->has('laraneat/location'))->toBe(true)
        ->and($this->repository->has('laraneat/navigation'))->toBe(false)
        ->and($this->repository->has('laraneat/book'))->toBe(false)
        ->and($this->repository->has('laraneat/author/some'))->toBe(false)
        ->and($this->repository->has('laraneat'))->toBe(false);
});

it('can count the number of modules', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    expect($this->repository->count())->toBe(5);
});

it('can find a module', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    expect($this->repository->find('laraneat/article')?->toArray())->toBe([
        'path' => $this->app->basePath('/modules/article'),
        'packageName' => 'laraneat/article',
        'name' => 'article',
        'namespace' => 'Modules\\Article',
        'providers' => [
            'Modules\\Article\\Providers\\ArticleServiceProvider',
            'Modules\\Article\\Providers\\RouteServiceProvider',
        ],
        'aliases' => [],
    ])
        ->and($this->repository->find('laraneat')?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/article/bar')?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/location')?->toArray())->toBe([
            'path' => $this->app->basePath('/modules/navigation'),
            'packageName' => 'laraneat/location',
            'name' => 'navigation',
            'namespace' => 'Modules\\GeoLocation',
            'providers' => [
                'Modules\\GeoLocation\\Providers\\GeoLocationServiceProvider',
                'Modules\\GeoLocation\\Providers\\RouteServiceProvider'
            ],
            'aliases' => []
        ])
        ->and($this->repository->find('laraneat/navigation')?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/book')?->toArray())->toBe(null);
});

it('throws an exception when the module is not found', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    $this->repository->findOrFail('laraneat/book');
})->throws(ModuleNotFound::class);

it('throws an exception when trying to remove a module that does not exist', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    expect($this->repository->delete('laraneat/book'));
})->throws(ModuleNotFound::class);

it('can filter modules by name', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    $articleModuleMatch = [
        'laraneat/article' => [
            'path' => $this->app->basePath('/modules/article'),
            'packageName' => 'laraneat/article',
            'name' => 'article',
            'namespace' => 'Modules\\Article',
            'providers' => [
                'Modules\\Article\\Providers\\ArticleServiceProvider',
                'Modules\\Article\\Providers\\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
    ];

    $locationModuleMatch = [
        'laraneat/location' => [
            'path' => $this->app->basePath('/modules/navigation'),
            'packageName' => 'laraneat/location',
            'name' => 'navigation',
            'namespace' => 'Modules\\GeoLocation',
            'providers' => [
                'Modules\\GeoLocation\\Providers\\GeoLocationServiceProvider',
                'Modules\\GeoLocation\\Providers\\RouteServiceProvider'
            ],
            'aliases' => []
        ],
    ];

    expect(collect($this->repository->filterByName('article'))->toArray())->toBe($articleModuleMatch)
        ->and(collect($this->repository->filterByName('Article'))->toArray())->toBe($articleModuleMatch)
        ->and(collect($this->repository->filterByName('ARTICLE'))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('aarticle'))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('location'))->toArray())->toBe($locationModuleMatch)
        ->and(collect($this->repository->filterByName('navigation'))->toArray())->toBe($locationModuleMatch)
        ->and(collect($this->repository->filterByName('Navigation'))->toArray())->toBe($locationModuleMatch)
        ->and(collect($this->repository->filterByName('GeoLocation'))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('foo'))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('laraneat'))->toArray())->toBe([]);

});

it('throws an exception when modules with the requested name are not found', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    $articleModuleMatch = [
        'laraneat/article' => [
            'path' => $this->app->basePath('/modules/article'),
            'packageName' => 'laraneat/article',
            'name' => 'article',
            'namespace' => 'Modules\\Article',
            'providers' => [
                'Modules\\Article\\Providers\\ArticleServiceProvider',
                'Modules\\Article\\Providers\\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
    ];

    expect(collect($this->repository->filterByNameOrFail('article'))->toArray())->toBe($articleModuleMatch);
    expect(fn() => $this->repository->filterByNameOrFail('book'))->toThrow(ModuleNotFound::class);
});

it('can delete a module', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    $this->instance(Composer::class, $this->mockComposer(['composer', 'remove', 'laraneat/article']));

    expect($this->repository->has('laraneat/article'))->toBe(true)
        ->and($this->repository->delete('laraneat/article'))->toBe(true)
        ->and($this->repository->has('laraneat/article'))->toBe(false);
});

it('can sync modules with composer', function () {
    $this->setModules([
        __DIR__ . '/fixtures/stubs/modules/valid/article',
        __DIR__ . '/fixtures/stubs/modules/valid/article-category',
        __DIR__ . '/fixtures/stubs/modules/valid/author',
        __DIR__ . '/fixtures/stubs/modules/valid/empty-module',
        __DIR__ . '/fixtures/stubs/modules/valid/navigation',
    ]);

    $this->instance(
        Composer::class,
        $this->mockComposer([
            'composer',
            'update',
            'laraneat/article-category',
            'laraneat/article',
            'laraneat/author',
            'laraneat/empty',
            'laraneat/location'
        ])
    );

    $this->backupComposerJson();
    $composerJsonPath = $this->app->basePath('/composer.json');
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
    $this->repository->syncWithComposer();
    assertFileExists($composerJsonPath);
    assertMatchesFileSnapshot($composerJsonPath);
    $this->resetComposerJson();

});
