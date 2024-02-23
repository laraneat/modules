<?php

use Laraneat\Modules\Enums\ModuleType;
use Laraneat\Modules\Exceptions\ModuleHasNonUniquePackageName;
use Laraneat\Modules\Exceptions\ModuleNotFound;
use Laraneat\Modules\ModulesRepository;

beforeEach(function () {
    $this->appModulesManifestPath = $this->app->bootstrapPath('cache/testing-laraneat-app-modules.php');
    $this->vendorModulesManifestPath = $this->app->bootstrapPath('cache/testing-laraneat-vendor-modules.php');
    $this->repository = new ModulesRepository(
        app: $this->app,
        basePath: $this->app->basePath(),
        appModulesManifestPath: $this->appModulesManifestPath,
        vendorModulesManifestPath: $this->vendorModulesManifestPath,
    );
});

afterEach(function () {
    $this->filesystem->delete([$this->appModulesManifestPath, $this->vendorModulesManifestPath]);
});

describe('scan paths', function () {
    it('sets the initial scan paths from the configuration', function () {
        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/app/Modules/*'),
        ]);
    });

    it('can add one scan path', function () {
        $this->repository->addScanPath($this->app->basePath('/foo_bar'));
        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/app/Modules/*'),
            $this->app->basePath('/foo_bar/*'),
        ]);
    });

    it('can add array of scan paths', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/foo/bar/test folder'),
            $this->app->basePath('/foo/test/'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/app/Modules/*'),
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
            $this->app->basePath('/app/Modules/*'),
            $this->app->basePath('/foo/bar/some1/*'),
            $this->app->basePath('/foo/bar/some2/*'),
            $this->app->basePath('/foo/bar/some3/*'),
            $this->app->basePath('/foo/bar/some4/*'),
        ]);
    });

    it('rejects vendor path', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/vendor'),
            $this->app->basePath('/vendor/'),
            $this->app->basePath('/vendor////'),
            $this->app->basePath('/vendor/*'),
            $this->app->basePath('/vendor/*//'),
            $this->app->basePath('/vendor/some/nested'),
            $this->app->basePath('/vendor/some/nested/'),
            $this->app->basePath('/vendor/some/nested/*'),
            $this->app->basePath('/vendor/some/nested/*////'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/app/Modules/*'),
        ]);
    });

    it('rejects adding a path that is already in the scan paths', function () {
        $this->repository->addScanPath([
            $this->app->basePath('/app/Modules'),
            $this->app->basePath('/app/Modules/'),
            $this->app->basePath('/app/Modules////'),
            $this->app->basePath('/app/Modules/*'),
            $this->app->basePath('/app/Modules/Nested'),
            $this->app->basePath('/app/Modules/Nested/'),
            $this->app->basePath('/app/Modules/Nested////'),
            $this->app->basePath('/app/Modules/Nested/*'),
            $this->app->basePath('/foo/test/'),
            $this->app->basePath('/foo/test////'),
        ]);

        expect($this->repository->getScanPaths())->toBe([
            $this->app->basePath('/app/Modules/*'),
            $this->app->basePath('/app/Modules/Nested/*'),
            $this->app->basePath('/foo/test/*'),
        ]);
    });
});

describe('modules manifest', function () {
    it('can build app modules manifest without caching', function () {
        $this->setAppModules([
            __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
            __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
        ]);

        $expectedManifest = [
            'laraneat/article' => [
                'name' => 'Article',
                'namespace' => 'App\Modules\Article\\',
                'providers' => [
                    'App\Modules\Article\Providers\ArticleServiceProvider',
                    'App\Modules\Article\Providers\RouteServiceProvider',
                ],
                'aliases' => [],
                'path' => $this->app->basePath('/app/Modules/Article'),
                'isVendor' => false,
            ],
            'laraneat/author' => [
                'name' => 'Author',
                'namespace' => 'App\Modules\Author\\',
                'providers' => [
                    'App\Modules\Author\Providers\AuthorServiceProvider',
                    'App\Modules\Author\Providers\RouteServiceProvider',
                ],
                'aliases' => [
                    'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
                ],
                'path' => $this->app->basePath('/app/Modules/Author'),
                'isVendor' => false,
            ],
        ];

        expect($this->repository->buildAppModulesManifest(false))
            ->toBe($expectedManifest)
            ->and($this->appModulesManifestPath)->not->toBeFile()
            ->and($this->repository->buildAppModulesManifest(true))
            ->toBe($expectedManifest)
            ->and($this->appModulesManifestPath)->toBeFile();

    });

    it('throws an exception when app modules have the same package names', function () {
        $this->setAppModules([
            __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
            __DIR__ . '/fixtures/stubs/modules/valid/app/ArticleCopy',
        ]);

        $this->repository->buildAppModulesManifest();
    })->throws(ModuleHasNonUniquePackageName::class);

    it('can build vendor modules manifest', function () {
        $this->setVendorModules([
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/article',
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
        ]);

        expect($this->repository->buildVendorModulesManifest())
            ->toBe([
                'laraneat/article' => [
                    'name' => 'Article',
                    'namespace' => 'Laraneat\Article\\',
                    'providers' => [
                        'Laraneat\Article\Providers\ArticleServiceProvider',
                        'Laraneat\Article\Providers\RouteServiceProvider',
                    ],
                    'aliases' => [],
                    'path' => $this->app->basePath('/vendor/laraneat/article'),
                    'isVendor' => true,
                ],
                'laraneat/foo' => [
                    'name' => 'Foo',
                    'namespace' => 'Laraneat\Foo\\',
                    'providers' => [
                        'Laraneat\Foo\Providers\FooServiceProvider',
                        'Laraneat\Foo\Providers\RouteServiceProvider',
                    ],
                    'aliases' => [],
                    'path' => $this->app->basePath('/vendor/laraneat/foo'),
                    'isVendor' => true,
                ],
                'laraneat/bar' => [
                    'name' => 'Bar',
                    'namespace' => 'Laraneat\Bar\\',
                    'providers' => [
                        'Laraneat\Bar\Providers\BarServiceProvider',
                        'Laraneat\Bar\Providers\RouteServiceProvider',
                    ],
                    'aliases' => [],
                    'path' => $this->app->basePath('/vendor/laraneat/bar'),
                    'isVendor' => true,
                ],
            ])
            ->and($this->vendorModulesManifestPath)
            ->toBeFile();

    });

    it('can prune app modules manifest', function () {
        $this->setAppModules([
            __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
            __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
        ]);

        $this->repository->buildAppModulesManifest(true);
        expect($this->appModulesManifestPath)->toBeFile();
        $this->repository->pruneAppModulesManifest();
        expect($this->appModulesManifestPath)->not->toBeFile();
    });

    it('can prune vendor modules manifest', function () {
        $this->setVendorModules([
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/article',
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
            __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
        ]);

        $this->repository->buildVendorModulesManifest();
        expect($this->vendorModulesManifestPath)->toBeFile();
        $this->repository->pruneVendorModulesManifest();
        expect($this->vendorModulesManifestPath)->not->toBeFile();
    });
});

it('can return app modules', function () {
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->toArray())->toBe([
        'laraneat/article' => [
            'isVendor' => false,
            'packageName' => 'laraneat/article',
            'name' => 'Article',
            'path' => $this->app->basePath('/app/Modules/Article'),
            'namespace' => 'App\Modules\Article',
            'providers' => [
                'App\Modules\Article\Providers\ArticleServiceProvider',
                'App\Modules\Article\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/author' => [
            'isVendor' => false,
            'packageName' => 'laraneat/author',
            'name' => 'Author',
            'path' => $this->app->basePath('app/Modules/Author'),
            'namespace' => 'App\Modules\Author',
            'providers' => [
                'App\Modules\Author\Providers\AuthorServiceProvider',
                'App\Modules\Author\Providers\RouteServiceProvider',
            ],
            'aliases' => [
                'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
            ],
        ],
    ]);
});

it('can return vendor modules', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/article',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);

    expect($this->repository->toArray())->toBe([
        'laraneat/article' => [
            'isVendor' => true,
            'packageName' => 'laraneat/article',
            'name' => 'Article',
            'path' => $this->app->basePath('/vendor/laraneat/article'),
            'namespace' => 'Laraneat\Article',
            'providers' => [
                'Laraneat\Article\Providers\ArticleServiceProvider',
                'Laraneat\Article\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/foo' => [
            'isVendor' => true,
            'packageName' => 'laraneat/foo',
            'name' => 'Foo',
            'path' => $this->app->basePath('/vendor/laraneat/foo'),
            'namespace' => 'Laraneat\Foo',
            'providers' => [
                'Laraneat\Foo\Providers\FooServiceProvider',
                'Laraneat\Foo\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/bar' => [
            'isVendor' => true,
            'packageName' => 'laraneat/bar',
            'name' => 'Bar',
            'path' => $this->app->basePath('/vendor/laraneat/bar'),
            'namespace' => 'Laraneat\Bar',
            'providers' => [
                'Laraneat\Bar\Providers\BarServiceProvider',
                'Laraneat\Bar\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
    ]);
});

it('can return all modules', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->toArray())->toBe([
        'laraneat/foo' => [
            'isVendor' => true,
            'packageName' => 'laraneat/foo',
            'name' => 'Foo',
            'path' => $this->app->basePath('/vendor/laraneat/foo'),
            'namespace' => 'Laraneat\Foo',
            'providers' => [
                'Laraneat\Foo\Providers\FooServiceProvider',
                'Laraneat\Foo\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/bar' => [
            'isVendor' => true,
            'packageName' => 'laraneat/bar',
            'name' => 'Bar',
            'path' => $this->app->basePath('/vendor/laraneat/bar'),
            'namespace' => 'Laraneat\Bar',
            'providers' => [
                'Laraneat\Bar\Providers\BarServiceProvider',
                'Laraneat\Bar\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/article' => [
            'isVendor' => false,
            'packageName' => 'laraneat/article',
            'name' => 'Article',
            'path' => $this->app->basePath('/app/Modules/Article'),
            'namespace' => 'App\Modules\Article',
            'providers' => [
                'App\Modules\Article\Providers\ArticleServiceProvider',
                'App\Modules\Article\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/author' => [
            'isVendor' => false,
            'packageName' => 'laraneat/author',
            'name' => 'Author',
            'path' => $this->app->basePath('app/Modules/Author'),
            'namespace' => 'App\Modules\Author',
            'providers' => [
                'App\Modules\Author\Providers\AuthorServiceProvider',
                'App\Modules\Author\Providers\RouteServiceProvider',
            ],
            'aliases' => [
                'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
            ],
        ],
    ]);
});

it('throws an exception when app and vendor modules have the same package names', function () {
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
    ]);
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/article',
    ]);

    $this->repository->getModules();
})->throws(ModuleHasNonUniquePackageName::class);

it('takes into account ignored modules', function () {
    $this->setLaraneatDontDiscover([
        'laraneat/article',
        'laraneat/bar'
    ]);

    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->toArray())->toBe([
        'laraneat/foo' => [
            'isVendor' => true,
            'packageName' => 'laraneat/foo',
            'name' => 'Foo',
            'path' => $this->app->basePath('/vendor/laraneat/foo'),
            'namespace' => 'Laraneat\Foo',
            'providers' => [
                'Laraneat\Foo\Providers\FooServiceProvider',
                'Laraneat\Foo\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ],
        'laraneat/author' => [
            'isVendor' => false,
            'packageName' => 'laraneat/author',
            'name' => 'Author',
            'path' => $this->app->basePath('/app/Modules/Author'),
            'namespace' => 'App\Modules\Author',
            'providers' => [
                'App\Modules\Author\Providers\AuthorServiceProvider',
                'App\Modules\Author\Providers\RouteServiceProvider',
            ],
            'aliases' => [
                'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
            ],
        ],
    ]);
});

it('takes into account ignored all modules', function () {
    $this->setLaraneatDontDiscover(['*']);

    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->toArray())->toBe([]);
});

it('can return providers', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->getProviders())->toBe([
        'Laraneat\Foo\Providers\FooServiceProvider',
        'Laraneat\Foo\Providers\RouteServiceProvider',
        'Laraneat\Bar\Providers\BarServiceProvider',
        'Laraneat\Bar\Providers\RouteServiceProvider',
        'App\Modules\Article\Providers\ArticleServiceProvider',
        'App\Modules\Article\Providers\RouteServiceProvider',
        'App\Modules\Author\Providers\AuthorServiceProvider',
        'App\Modules\Author\Providers\RouteServiceProvider',
    ])
        ->and($this->repository->getProviders(ModuleType::Vendor))->toBe([
            'Laraneat\Foo\Providers\FooServiceProvider',
            'Laraneat\Foo\Providers\RouteServiceProvider',
            'Laraneat\Bar\Providers\BarServiceProvider',
            'Laraneat\Bar\Providers\RouteServiceProvider',
        ])
        ->and($this->repository->getProviders(ModuleType::App))->toBe([
            'App\Modules\Article\Providers\ArticleServiceProvider',
            'App\Modules\Article\Providers\RouteServiceProvider',
            'App\Modules\Author\Providers\AuthorServiceProvider',
            'App\Modules\Author\Providers\RouteServiceProvider',
        ]);

});

it('can return aliases', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->getAliases())->toBe([
        'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
    ])
        ->and($this->repository->getAliases(ModuleType::Vendor))->toBe([])
        ->and($this->repository->getAliases(ModuleType::App))->toBe([
            'AuthorFacade' => 'App\Modules\Author\Facades\SomeFacade'
        ]);

});

it('can check the module for existence', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->has('laraneat/foo'))->toBe(true)
        ->and($this->repository->has('laraneat/bar'))->toBe(true)
        ->and($this->repository->has('laraneat/article'))->toBe(true)
        ->and($this->repository->has('laraneat/author'))->toBe(true)
        ->and($this->repository->has('laraneat/book'))->toBe(false)
        ->and($this->repository->has('laraneat/author/some'))->toBe(false)
        ->and($this->repository->has('laraneat'))->toBe(false)
        ->and($this->repository->has('laraneat/foo', ModuleType::Vendor))->toBe(true)
        ->and($this->repository->has('laraneat/foo', ModuleType::App))->toBe(false)
        ->and($this->repository->has('laraneat/article', ModuleType::Vendor))->toBe(false)
        ->and($this->repository->has('laraneat/article', ModuleType::App))->toBe(true);


});

it('can count the number of modules', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Foo',
    ]);

    expect($this->repository->count())->toBe(5)
        ->and($this->repository->count(ModuleType::App))->toBe(3)
        ->and($this->repository->count(ModuleType::Vendor))->toBe(2);
});

it('can find a module', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->find('laraneat/article')?->toArray())->toBe([
        'isVendor' => false,
        'packageName' => 'laraneat/article',
        'name' => 'Article',
        'path' => $this->app->basePath('/app/Modules/Article'),
        'namespace' => 'App\Modules\Article',
        'providers' => [
            'App\Modules\Article\Providers\ArticleServiceProvider',
            'App\Modules\Article\Providers\RouteServiceProvider',
        ],
        'aliases' => [],
    ])
        ->and($this->repository->find('laraneat/article', ModuleType::Vendor)?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/article', ModuleType::App)?->toArray())->toBe([
            'isVendor' => false,
            'packageName' => 'laraneat/article',
            'name' => 'Article',
            'path' => $this->app->basePath('/app/Modules/Article'),
            'namespace' => 'App\Modules\Article',
            'providers' => [
                'App\Modules\Article\Providers\ArticleServiceProvider',
                'App\Modules\Article\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ])
        ->and($this->repository->find('laraneat/foo')?->toArray())->toBe([
            'isVendor' => true,
            'packageName' => 'laraneat/foo',
            'name' => 'Foo',
            'path' => $this->app->basePath('/vendor/laraneat/foo'),
            'namespace' => 'Laraneat\Foo',
            'providers' => [
                'Laraneat\Foo\Providers\FooServiceProvider',
                'Laraneat\Foo\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ])
        ->and($this->repository->find('laraneat/foo', ModuleType::App)?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/foo', ModuleType::Vendor)?->toArray())->toBe([
            'isVendor' => true,
            'packageName' => 'laraneat/foo',
            'name' => 'Foo',
            'path' => $this->app->basePath('/vendor/laraneat/foo'),
            'namespace' => 'Laraneat\Foo',
            'providers' => [
                'Laraneat\Foo\Providers\FooServiceProvider',
                'Laraneat\Foo\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ])
        ->and($this->repository->find('laraneat')?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/foo/bar')?->toArray())->toBe(null)
        ->and($this->repository->find('laraneat/book')?->toArray())->toBe(null);
});

it('throws an exception when the module is not found', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->findOrFail('laraneat/book')->toArray())->toBe(null);
})->throws(ModuleNotFound::class);

it('can delete app module', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->has('laraneat/article'))->toBe(true)
        ->and($this->repository->delete('laraneat/article'))->toBe(true)
        ->and($this->repository->has('laraneat/article'))->toBe(false);
});

it('throws an exception when trying to remove a module that does not exist', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect($this->repository->delete('laraneat/articles'));
})->throws(ModuleNotFound::class);

it('can filter modules by name', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    expect(collect($this->repository->filterByName('Article'))->toArray())->toBe([
        'laraneat/article' => [
            'isVendor' => false,
            'packageName' => 'laraneat/article',
            'name' => 'Article',
            'path' => $this->app->basePath('/app/Modules/Article'),
            'namespace' => 'App\Modules\Article',
            'providers' => [
                'App\Modules\Article\Providers\ArticleServiceProvider',
                'App\Modules\Article\Providers\RouteServiceProvider',
            ],
            'aliases' => [],
        ]
    ])
        ->and(collect($this->repository->filterByName('Article', ModuleType::Vendor))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('Article', ModuleType::App))->toArray())->toBe([
            'laraneat/article' => [
                'isVendor' => false,
                'packageName' => 'laraneat/article',
                'name' => 'Article',
                'path' => $this->app->basePath('/app/Modules/Article'),
                'namespace' => 'App\Modules\Article',
                'providers' => [
                    'App\Modules\Article\Providers\ArticleServiceProvider',
                    'App\Modules\Article\Providers\RouteServiceProvider',
                ],
                'aliases' => [],
            ]
        ])
        ->and(collect($this->repository->filterByName('Foo'))->toArray())->toBe([
            'laraneat/foo' => [
                'isVendor' => true,
                'packageName' => 'laraneat/foo',
                'name' => 'Foo',
                'path' => $this->app->basePath('/vendor/laraneat/foo'),
                'namespace' => 'Laraneat\Foo',
                'providers' => [
                    'Laraneat\Foo\Providers\FooServiceProvider',
                    'Laraneat\Foo\Providers\RouteServiceProvider',
                ],
                'aliases' => [],
            ]
        ])
        ->and(collect($this->repository->filterByName('Foo', ModuleType::App))->toArray())->toBe([])
        ->and(collect($this->repository->filterByName('Foo', ModuleType::Vendor))->toArray())->toBe([
            'laraneat/foo' => [
                'isVendor' => true,
                'packageName' => 'laraneat/foo',
                'name' => 'Foo',
                'path' => $this->app->basePath('/vendor/laraneat/foo'),
                'namespace' => 'Laraneat\Foo',
                'providers' => [
                    'Laraneat\Foo\Providers\FooServiceProvider',
                    'Laraneat\Foo\Providers\RouteServiceProvider',
                ],
                'aliases' => [],
            ]
        ])
        ->and(collect($this->repository->filterByName('Book'))->toArray())->toBe([]);

});

it('throws an exception when modules with the requested name are not found', function () {
    $this->setVendorModules([
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/foo',
        __DIR__ . '/fixtures/stubs/modules/valid/vendor/laraneat/bar',
    ]);
    $this->setAppModules([
        __DIR__ . '/fixtures/stubs/modules/valid/app/Article',
        __DIR__ . '/fixtures/stubs/modules/valid/app/Author',
    ]);

    $this->repository->filterByNameOrFail('laraneat/book');
})->throws(ModuleNotFound::class);
