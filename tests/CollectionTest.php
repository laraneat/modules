<?php

namespace Laraneat\Modules\Tests;

use Laraneat\Modules\Collection;
use Laraneat\Modules\Module;

class CollectionTest extends BaseTestCase
{
    public function testToArraySetsPathAttribute(): void
    {
        $moduleOnePath = __DIR__ . '/stubs/valid/Article';
        $moduleTwoPath = __DIR__ . '/stubs/valid/Requirement';
        $modules = [
            new Module($this->app, 'module-one', $moduleOnePath, 'App\\Module\\Article'),
            new Module($this->app, 'module-two', $moduleTwoPath, 'App\\Module\\Requirement'),
        ];
        $collection = new Collection($modules);
        $collectionArray = $collection->toArray();

        $this->assertArrayHasKey('path', $collectionArray[0]);
        $this->assertEquals($moduleOnePath, $collectionArray[0]['path']);
        $this->assertArrayHasKey('path', $collectionArray[1]);
        $this->assertEquals($moduleTwoPath, $collectionArray[1]['path']);
    }

    public function testGetItemsReturnsTheCollectionItems(): void
    {
        $modules = [
            new Module($this->app, 'module-one', __DIR__ . '/stubs/valid/Article', 'App\\Module\\Article'),
            new Module($this->app, 'module-two', __DIR__ . '/stubs/valid/Requirement', 'App\\Module\\Requirement'),
        ];
        $collection = new Collection($modules);
        $items = $collection->getItems();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Module::class, $items[0]);
    }
}
