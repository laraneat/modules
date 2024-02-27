<?php

use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\InvalidConfigValue;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

describe('normalizePath()', function() {
    it('correctly normalizes path', function() {
        expect(GeneratorHelper::normalizePath('src/some/Actions'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some/Actions'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some\\Actions/'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some/Actions///'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('////src/some/Actions\\'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('////src\\some/Actions////'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('\\src\\some\\Actions'))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('\\\\src\\some\\Actions\\\\'))->toBe('src/some/Actions');
    });

    it('correctly normalizes path using rtrim', function() {
        expect(GeneratorHelper::normalizePath('src/some/Actions', true))->toBe('src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some/Actions', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some\\Actions/', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('/src/some/Actions///', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('////src/some/Actions\\', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('////src\\some/Actions////', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('\\src\\some\\Actions', true))->toBe('/src/some/Actions');
        expect(GeneratorHelper::normalizePath('\\\\src\\some\\Actions\\\\', true))->toBe('/src/some/Actions');
    });
});

describe('normalizeNamespace()', function() {
    it('correctly normalizes namespace', function() {
        expect(GeneratorHelper::normalizeNamespace('src/some/Actions'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some/Actions'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some\\Actions/'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some/Actions///'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('////src/some/Actions\\'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('////src\\some/Actions////'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('\\src\\some\\Actions'))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('\\\\src\\some\\Actions\\\\'))->toBe('src\\some\\Actions');
    });

    it('correctly normalizes namespace using rtrim', function() {
        expect(GeneratorHelper::normalizeNamespace('src/some/Actions', true))->toBe('src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some/Actions', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some\\Actions/', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('/src/some/Actions///', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('////src/some/Actions\\', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('////src\\some/Actions////', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('\\src\\some\\Actions', true))->toBe('\\src\\some\\Actions');
        expect(GeneratorHelper::normalizeNamespace('\\\\src\\some\\Actions\\\\', true))->toBe('\\src\\some\\Actions');
    });
});

describe('makeRelativePath()', function() {
    it('correctly make relative', function() {
        expect(GeneratorHelper::makeRelativePath('', ''))->toBe('');
        expect(GeneratorHelper::makeRelativePath('/', ''))->toBe(null);
        expect(GeneratorHelper::makeRelativePath('', '/'))->toBe(null);
        expect(GeneratorHelper::makeRelativePath('src/some/foo/bar', 'src/some/foo/bar'))->toBe('');
        expect(GeneratorHelper::makeRelativePath('/src/some/foo/bar', 'src/some/foo/bar'))->toBe(null);
        expect(GeneratorHelper::makeRelativePath('src/some/foo/bar', '/src/some/foo/bar'))->toBe(null);
        expect(GeneratorHelper::makeRelativePath('src/some/foo/bar', 'src/some'))->toBe('../..');
        expect(GeneratorHelper::makeRelativePath('src/some', 'src/some/foo/bar'))->toBe('foo/bar');
        expect(GeneratorHelper::makeRelativePath('/src/some', '/src/some/foo/bar'))->toBe('foo/bar');
        expect(GeneratorHelper::makeRelativePath('src/some/bar', 'src/some/foo/bar'))->toBe('../foo/bar');
        expect(GeneratorHelper::makeRelativePath('src/some/foo/bar', 'src/some/bar'))->toBe('../../bar');
        expect(GeneratorHelper::makeRelativePath('src/some/bar/baz', 'src/some/foo/bar/baz'))->toBe('../../foo/bar/baz');
        expect(GeneratorHelper::makeRelativePath('src/some/foo/bar/baz', 'src/some/bar/baz'))->toBe('../../../bar/baz');
        expect(GeneratorHelper::makeRelativePath('/src/some/foo/bar/baz', '/src/some/bar/baz'))->toBe('../../../bar/baz');
        expect(GeneratorHelper::makeRelativePath('/src/some/foo/bar/baz', '////src/some/bar/baz'))->toBe('../../../bar/baz');
        expect(GeneratorHelper::makeRelativePath('/////src/some/foo/bar/baz', '/src/some/bar/baz'))->toBe('../../../bar/baz');
        expect(GeneratorHelper::makeRelativePath('foo/bar/baz', 'src/some/bar/baz'))->toBe(null);
    });
});

describe('getBasePath()', function() {
    it('returns base path', function() {
        $this->app['config']->set('modules.path', $this->app->basePath('/foo/bar'));

        expect(GeneratorHelper::getBasePath())->toBe($this->app->basePath('/foo/bar'));

        $this->app['config']->set('modules.path', $this->app->basePath('/foo/bar/') . '///');

        expect(GeneratorHelper::getBasePath())->toBe($this->app->basePath('/foo/bar'));
    });

    it('throws an error when the modules path is not defined', function() {
        $this->app['config']->set('modules.path', '');

        expect(fn() => GeneratorHelper::getBasePath())->toThrow(InvalidConfigValue::class);
    });
});

describe('getBaseNamespace()', function() {
    it('returns base namespace', function() {
        $this->app['config']->set('modules.namespace', '\\Foo\\Bar\\');

        expect(GeneratorHelper::getBaseNamespace())->toBe('Foo\\Bar');
    });

    it('throws an error when the modules namespace is not defined', function() {
        $this->app['config']->set('modules.namespace', '');

        expect(fn() => GeneratorHelper::getBaseNamespace())->toThrow(InvalidConfigValue::class);
    });
});

describe('component(ModuleComponentType $componentType)', function() {
    it('returns component config by type', function() {
        $this->app['config']->set('modules.components.' . ModuleComponentType::Action->value, [
            'path' => '///Some/Actions///',
        ]);

        expect(GeneratorHelper::component(ModuleComponentType::Action)->getPath())->toBe('Some/Actions');
        expect(GeneratorHelper::component(ModuleComponentType::Action)->getNamespace())->toBe('Some\\Actions');

        $this->app['config']->set('modules.components.' . ModuleComponentType::Action->value, [
            'path' => '///src/Some/Actions///',
            'namespace' => '\\\\\\Some\\Actions\\\\\\'
        ]);

        expect(GeneratorHelper::component(ModuleComponentType::Action)->getPath())->toBe('src/Some/Actions');
        expect(GeneratorHelper::component(ModuleComponentType::Action)->getNamespace())->toBe('Some\\Actions');
    });

    it('throws an error when the component has invalid config', function() {
        $this->app['config']->set('modules.components.' . ModuleComponentType::Action->value, 'foo');
        expect(fn() => GeneratorHelper::component(ModuleComponentType::Action))->toThrow(InvalidConfigValue::class);

        $this->app['config']->set('modules.components.' . ModuleComponentType::Action->value, ['namespace' => 'Some\\Actions']);
        expect(fn() => GeneratorHelper::component(ModuleComponentType::Action))->toThrow(InvalidConfigValue::class);
    });
});
