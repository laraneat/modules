<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Support\Collection;
use Laraneat\Modules\Module;

class CollectionTest extends BaseTestCase
{
    public function testToArraySetsPathAttribute(): void
    {
        $moduleOneAttributes = [
            'name' => 'module-one',
            'path' => __DIR__ . '/fixtures/stubs/valid/Article',
            'namespace' => 'App\\SomeModules\\Article'
        ];
        $moduleTwoAttributes = [
            'name' => 'module-two',
            'path' =>__DIR__ . '/fixtures/stubs/valid/Requirement',
            'namespace' => 'App\\SomeModules\\Requirement'
        ];

        $modules = [
            'article' => new Module(
                $this->app,
                $moduleOneAttributes['name'],
                $moduleOneAttributes['path'],
                $moduleOneAttributes['namespace']
            ),
            'requirement' => new Module(
                $this->app,
                $moduleTwoAttributes['name'],
                $moduleTwoAttributes['path'],
                $moduleTwoAttributes['namespace']
            ),
        ];
        $moduleOneJson = $modules['article']->json()->toArray();
        $moduleTwoJson = $modules['requirement']->json()->toArray();
        $collection = new Collection($modules);
        $collectionArray = $collection->toArray();

        $this->assertArrayHasKey('path', $collectionArray['article']);
        $this->assertEquals($moduleOneAttributes['path'], $collectionArray['article']['path']);
        $this->assertEquals($moduleOneAttributes['name'], $collectionArray['article']['name']);
        $this->assertEquals($moduleOneAttributes['namespace'], $collectionArray['article']['namespace']);
        $this->assertEquals($moduleOneJson, $collectionArray['article']['module_json']['module.json']);

        $this->assertArrayHasKey('path', $collectionArray['requirement']);
        $this->assertEquals($moduleTwoAttributes['path'], $collectionArray['requirement']['path']);
        $this->assertEquals($moduleTwoAttributes['name'], $collectionArray['requirement']['name']);
        $this->assertEquals($moduleTwoAttributes['namespace'], $collectionArray['requirement']['namespace']);
        $this->assertEquals($moduleTwoJson, $collectionArray['requirement']['module_json']['module.json']);
    }

    public function testMethodAllReturnsTheCollectionItems(): void
    {
        $modules = [
            'article' => new Module($this->app, 'module-one', __DIR__ . '/fixtures/stubs/valid/Article', 'App\\Module\\Article'),
            'requirement' => new Module($this->app, 'module-two', __DIR__ . '/fixtures/stubs/valid/Requirement', 'App\\Module\\Requirement'),
        ];
        $collection = new Collection($modules);
        $items = $collection->all();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Module::class, $items['article']);
        $this->assertInstanceOf(Module::class, $items['requirement']);
    }
}
