<?php

namespace Laraneat\Modules\Tests;

use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Exceptions\InvalidAssetPath;
use Laraneat\Modules\Exceptions\ModuleNotFoundException;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Module;

class FileRepositoryTest extends BaseTestCase
{
    private FileRepository $repository;
    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new FileRepository($this->app);
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    /** @test */
    public function it_adds_location_to_paths()
    {
        $this->repository->addLocation('some/path');

        $paths = $this->repository->getPaths();
        $this->assertCount(1, $paths);
        $this->assertEquals('some/path', $paths[0]);
    }

    /** @test */
    public function it_returns_all_enabled_modules()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->assertCount(0, $this->repository->getByStatus(true));
        $this->assertCount(0, $this->repository->allEnabled());
    }

    /** @test */
    public function it_returns_all_disabled_modules()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->assertCount(2, $this->repository->getByStatus(false));
        $this->assertCount(2, $this->repository->allDisabled());
    }

    /** @test */
    public function it_counts_all_modules()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->assertEquals(2, $this->repository->count());
    }

    /** @test */
    public function it_finds_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->assertInstanceOf(Module::class, $this->repository->find('article'));
    }

    /** @test */
    public function it_finds_a_module_by_alias()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->assertInstanceOf(Module::class, $this->repository->findByAlias('article'));
        $this->assertInstanceOf(Module::class, $this->repository->findByAlias('required_module'));
    }

    /** @test */
    public function it_find_or_fail_throws_exception_if_module_not_found()
    {
        $this->expectException(ModuleNotFoundException::class);

        $this->repository->findOrFail('something');
    }

    /** @test */
    public function it_finds_the_module_asset_path()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid/Article');
        $assetPath = $this->repository->assetPath('article');

        $this->assertEquals(public_path('modules/article'), $assetPath);
    }

    /** @test */
    public function it_gets_the_used_storage_path()
    {
        $path = $this->repository->getUsedStoragePath();

        $this->assertEquals(storage_path('app/modules/modules.used'), $path);
    }

    /** @test */
    public function it_sets_used_module()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->repository->setUsed('Article');

        $this->assertEquals('Article', $this->repository->getUsedNow());
    }

    /** @test */
    public function it_gets_the_assets_path()
    {
        $this->assertEquals(public_path('modules'), $this->repository->getAssetsPath());
    }

    /** @test */
    public function it_gets_a_specific_module_asset()
    {
        $path = $this->repository->asset('article:test.js');

        $this->assertEquals('//localhost/modules/article/test.js', $path);
    }

    /** @test */
    public function it_throws_exception_if_module_is_omitted()
    {
        $this->expectException(InvalidAssetPath::class);
        $this->expectExceptionMessage('Module name was not specified in asset [test.js].');

        $this->repository->asset('test.js');
    }

    /** @test */
    public function it_can_detect_if_module_is_active()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->repository->enable('Article');

        $this->assertTrue($this->repository->isEnabled('Article'));
    }

    /** @test */
    public function it_can_detect_if_module_is_inactive()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->repository->isDisabled('Article');

        $this->assertTrue($this->repository->isDisabled('Article'));
    }

    /** @test */
    public function it_can_disabled_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->repository->disable('Article');

        $this->assertTrue($this->repository->isDisabled('Article'));
    }

    /** @test */
    public function it_can_enable_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $this->repository->enable('Article');

        $this->assertTrue($this->repository->isEnabled('Article'));
    }

    /** @test */
    public function it_can_delete_a_module()
    {
        $this->artisan('module:make', ['name' => 'Article']);

        $this->repository->delete('Article');

        $this->assertDirectoryDoesNotExist(base_path('app/Modules/Article'));
    }

    /** @test */
    public function it_can_find_all_requirements_of_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');

        $requirements = $this->repository->findRequirements('Article');

        $this->assertCount(1, $requirements);
        $this->assertInstanceOf(Module::class, $requirements[0]);
    }

    /** @test */
    public function it_can_register_macros()
    {
        Module::macro('registeredMacro', function () {});

        $this->assertTrue(Module::hasMacro('registeredMacro'));
    }

    /** @test */
    public function it_does_not_have_unregistered_macros()
    {
        $this->assertFalse(Module::hasMacro('unregisteredMacro'));
    }

    /** @test */
    public function it_calls_macros_on_modules()
    {
        Module::macro('getReverseName', function () {
            return strrev($this->getName());
        });

        $this->repository->addLocation(__DIR__ . '/fixtures/stubs/valid');
        $module = $this->repository->find('article');

        $this->assertEquals('elcitrA', $module->getReverseName());
    }
}
