<?php

namespace Laraneat\Modules\Tests;

use App\Modules\Article\Providers\DeferredServiceProvider;
use App\Modules\Article\Providers\ArticleServiceProvider;
use Illuminate\Support\Facades\Event;
use Laraneat\Modules\Contracts\ActivatorInterface;

class ModuleTest extends BaseTestCase
{
    private TestingModule $module;
    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new TestingModule(
            $this->app,
            'Article Name',
            __DIR__ . '/fixtures/stubs/valid/Article',
            'App\\Modules\\Article'
        );
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        symlink(__DIR__ . '/fixtures/stubs/valid', __DIR__ . '/fixtures/stubs/valid_symlink');
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        unlink(__DIR__ . '/fixtures/stubs/valid_symlink');
    }

    /** @test */
    public function it_gets_module_name()
    {
        $this->assertEquals('Article Name', $this->module->getName());
    }

    /** @test */
    public function it_gets_module_key()
    {
        $this->assertEquals('article-name', $this->module->getKey());
    }

    /** @test */
    public function it_gets_studly_name()
    {
        $this->assertEquals('ArticleName', $this->module->getStudlyName());
    }

    /** @test */
    public function it_gets_snake_name()
    {
        $this->assertEquals('article_name', $this->module->getSnakeName());
    }

    /** @test */
    public function it_gets_module_description()
    {
        $this->assertEquals('article module', $this->module->getDescription());
    }

    /** @test */
    public function it_gets_module_alias()
    {
        $this->assertEquals('article', $this->module->getAlias());
    }

    /** @test */
    public function it_gets_module_path()
    {
        $this->assertEquals(__DIR__ . '/fixtures/stubs/valid/Article', $this->module->getPath());
    }

    /** @test */
    public function it_gets_module_path_with_symlink()
    {
        // symlink created in setUpBeforeClass

        $this->module = new TestingModule(
            $this->app,
            'Article Name',
            __DIR__ . '/fixtures/stubs/valid_symlink/Article',
            'App\\Module\\Article'
        );

        $this->assertEquals(__DIR__ . '/fixtures/stubs/valid_symlink/Article', $this->module->getPath());

        // symlink deleted in tearDownAfterClass
    }

    /** @test */
    public function it_gets_required_modules()
    {
        $this->assertEquals(['required_module'], $this->module->getRequires());
    }

    /** @test */
    public function it_reads_module_json_files()
    {
        $jsonModule = $this->module->json();
        $composerJson = $this->module->json('composer.json');

        $this->assertEquals('0.1', $jsonModule->get('version'));
        $this->assertEquals('laraneat/article', $composerJson->get('name'));
    }

    /** @test */
    public function it_reads_key_from_module_json_file_via_helper_method()
    {
        $this->assertEquals('Article', $this->module->get('name'));
        $this->assertEquals('0.1', $this->module->get('version'));
        $this->assertEquals('my default', $this->module->get('some-thing-non-there', 'my default'));
        $this->assertEquals(['required_module'], $this->module->get('requires'));
    }

    /** @test */
    public function it_reads_key_from_composer_json_file_via_helper_method()
    {
        $this->assertEquals('laraneat/article', $this->module->getComposerAttr('name'));
    }

    /** @test */
    public function it_casts_module_to_string()
    {
        $this->assertEquals('ArticleName', (string) $this->module);
    }

    /** @test */
    public function it_module_status_check()
    {
        $this->assertFalse($this->module->isStatus(true));
        $this->assertTrue($this->module->isStatus(false));
    }

    /** @test */
    public function it_checks_module_enabled_status()
    {
        $this->assertFalse($this->module->isEnabled());
        $this->assertTrue($this->module->isDisabled());
    }

    /** @test */
    public function it_sets_active_status(): void
    {
        $this->module->setActive(true);
        $this->assertTrue($this->module->isEnabled());
        $this->module->setActive(false);
        $this->assertFalse($this->module->isEnabled());
    }

    /** @test */
    public function it_fires_events_when_module_is_enabled()
    {
        Event::fake();

        $this->module->enable();

        Event::assertDispatched(sprintf('modules.%s.enabling', $this->module->getKey()));
        Event::assertDispatched(sprintf('modules.%s.enabled', $this->module->getKey()));
    }

    /** @test */
    public function it_fires_events_when_module_is_disabled()
    {
        Event::fake();

        $this->module->disable();

        Event::assertDispatched(sprintf('modules.%s.disabling', $this->module->getKey()));
        Event::assertDispatched(sprintf('modules.%s.disabled', $this->module->getKey()));
    }

    /** @test */
    public function it_has_a_good_providers_manifest_path()
    {
        $this->assertEquals(
            $this->app->bootstrapPath("cache/{$this->module->getSnakeName()}_module.php"),
            $this->module->getCachedServicesPath()
        );
    }

    /** @test */
    public function it_makes_a_manifest_file_when_providers_are_loaded()
    {
        $cachedServicesPath = $this->module->getCachedServicesPath();

        @unlink($cachedServicesPath);
        $this->assertFileDoesNotExist($cachedServicesPath);

        $this->module->registerProviders();

        $this->assertFileExists($cachedServicesPath);
        $manifest = require $cachedServicesPath;

        $this->assertEquals([
            'providers' => [
                ArticleServiceProvider::class,
                DeferredServiceProvider::class,
            ],
            'eager'     => [ArticleServiceProvider::class],
            'deferred'  => ['deferred' => DeferredServiceProvider::class],
            'when'      =>
            [DeferredServiceProvider::class => []],
        ], $manifest);
    }

    /** @test */
    public function it_can_load_a_deferred_provider()
    {
        @unlink($this->module->getCachedServicesPath());

        $this->module->registerProviders();

        try {
            app('foo');
            $this->fail("app('foo') should throw an exception.");
        } catch (\Exception $e) {
            $this->assertEquals('Target class [foo] does not exist.', $e->getMessage());
        }

        app('deferred');

        $this->assertEquals('bar', app('foo'));
    }
}

class TestingModule extends \Laraneat\Modules\Module
{
    public function registerProviders(): void
    {
        parent::registerProviders();
    }
}
