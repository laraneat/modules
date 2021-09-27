<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class GeneratorHelperReaderTest extends BaseTestCase
{
    private Filesystem $finder;
    private string $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->modulePath = base_path('app/Modules/Article');
    }

    private function makeModule(): void
    {
        $this->artisan('module:make', ['name' => ['Article']]);
    }

    private function removeModule(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
    }

    /** @test */
    public function it_can_read_component_configuration()
    {
        $seedConfig = GeneratorHelper::component('seeder');

        $this->assertEquals('Data/Seeders', $seedConfig->getPath());
        $this->assertEquals('Data\\Seeders', $seedConfig->getNamespace());
        $this->assertTrue($seedConfig->generate());
        $this->assertFalse($seedConfig->withGitKeep());
    }

    /** @test */
    public function it_can_guess_namespace_from_path()
    {
        $this->app['config']->set('modules.generator.components.provider', [
            'path' => 'Base/Providers',
            'generate' => true,
            'gitkeep' => true
        ]);

        $config = GeneratorHelper::component('provider');

        $this->assertEquals('Base/Providers', $config->getPath());
        $this->assertEquals('Base\\Providers', $config->getNamespace());
        $this->assertTrue($config->generate());
        $this->assertTrue($config->withGitKeep());
    }

    /** @test */
    public function it_can_read_component_full_path_by_existing_module()
    {
        $this->makeModule();
        $resourceFullPath = GeneratorHelper::component('api-resource')->getFullPath('Article');

        $this->assertEquals(base_path('app/Modules/Article/UI/API/Resources'), $resourceFullPath);
        $this->removeModule();
    }

    /** @test */
    public function it_can_read_component_full_namespace_by_existing_module()
    {
        $this->makeModule();
        $resourceFullNamespace = GeneratorHelper::component('api-resource')->getFullNamespace('Article');

        $this->assertEquals('App\\Modules\\Article\\UI\\API\\Resources', $resourceFullNamespace);
        $this->removeModule();
    }

    /** @test */
    public function it_can_read_component_full_path_by_not_existing_module()
    {
        $resourceFullPath = GeneratorHelper::component('api-resource')->getFullPath('SomeModule');

        $this->assertEquals(base_path('app/Modules/SomeModule/UI/API/Resources'), $resourceFullPath);
    }

    /** @test */
    public function it_can_read_component_full_namespace_by_not_existing_module()
    {
        $resourceFullNamespace = GeneratorHelper::component('api-resource')->getFullNamespace('SomeModule');

        $this->assertEquals('App\\Modules\\SomeModule\\UI\\API\\Resources', $resourceFullNamespace);
    }
}
