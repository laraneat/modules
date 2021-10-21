<?php

namespace Laraneat\Modules\Tests\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @group command
 * @group generator
 */
class QueryWizardMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => 'Article', '--plain' => true]);
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Article');
        parent::tearDown();
    }

    /** @test */
    public function it_generates_query_wizard_file()
    {
        $code = $this->artisan('module:make:wizard', [
            'name' => 'MyAwesomeQueryWizard',
            'module' => 'Article',
            '-n' => true
        ]);

        $this->assertTrue(is_file($this->modulePath . '/UI/API/QueryWizards/MyAwesomeQueryWizard.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_eloquent_query_wizard_file_with_content()
    {
        $code = $this->artisan('module:make:wizard', [
            'name' => 'Foo/Bar\\MyAwesomeEloquentQueryWizard',
            'module' => 'Article',
            '--stub' => 'eloquent'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/QueryWizards/Foo/Bar/MyAwesomeEloquentQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_scout_query_wizard_file_with_content()
    {
        $code = $this->artisan('module:make:wizard', [
            'name' => 'Foo/Bar\\MyAwesomeScoutQueryWizard',
            'module' => 'Article',
            '--stub' => 'scout'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/QueryWizards/Foo/Bar/MyAwesomeScoutQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_model_query_wizard_file_with_content()
    {
        $code = $this->artisan('module:make:wizard', [
            'name' => 'Foo/Bar\\MyAwesomeModelQueryWizard',
            'module' => 'Article',
            '--stub' => 'model'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/QueryWizards/Foo/Bar/MyAwesomeModelQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_elastic_query_wizard_file_with_content()
    {
        $code = $this->artisan('module:make:wizard', [
            'name' => 'Foo/Bar\\MyAwesomeElasticQueryWizard',
            'module' => 'Article',
            '--stub' => 'elastic'
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/QueryWizards/Foo/Bar/MyAwesomeElasticQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_path_for_query_wizard_file()
    {
        $this->app['config']->set('modules.generator.components.api-query-wizard.path', 'Foo/Bar\\QueryWizards');

        $code = $this->artisan('module:make:wizard', [
            'name' => 'Baz\\Bat/MyAwesomeQueryWizard',
            'module' => 'Article',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/Foo/Bar/QueryWizards/Baz/Bat/MyAwesomeQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_for_query_wizard_file()
    {
        $this->app['config']->set('modules.generator.components.api-query-wizard.namespace', 'Foo/Bar\\QueryWizards/');

        $code = $this->artisan('module:make:wizard', [
            'name' => 'Baz\\Bat/MyAwesomeQueryWizard',
            'module' => 'Article',
            '-n' => true
        ]);

        $file = $this->finder->get($this->modulePath . '/UI/API/QueryWizards/Baz/Bat/MyAwesomeQueryWizard.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
