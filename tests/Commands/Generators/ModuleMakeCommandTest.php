<?php

namespace Laraneat\Modules\Tests\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @group command
 * @group generator
 */
class ModuleMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;
    private ActivatorInterface $activator;
    private RepositoryInterface $repository;
    private array $moduleComponentPaths;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('app/Modules/Article');
        $this->finder = $this->app['files'];
        $this->repository = $this->app[RepositoryInterface::class];
        $this->activator = $this->app[ActivatorInterface::class];
        $this->moduleComponentPaths = [
            'Actions/CreateArticleAction.php',
            'Actions/UpdateArticleAction.php',
            'Actions/DeleteArticleAction.php',
            'Actions/ViewArticleAction.php',
            'Actions/ListArticlesAction.php',
            'Data/Factories/ArticleFactory.php',
            'Data/Seeders/ArticlePermissionsSeeder_1.php',
            'DTO/CreateArticleDTO.php',
            'Models/Article.php',
            'Policies/ArticlePolicy.php',
            'Providers/ArticleServiceProvider.php',
            'Providers/RouteServiceProvider.php',
            'UI/API/QueryWizards/ArticleQueryWizard.php',
            'UI/API/QueryWizards/ArticlesQueryWizard.php',
            'UI/API/Resources/ArticleResource.php',
            'UI/API/Requests/CreateArticleRequest.php',
            'UI/API/Requests/UpdateArticleRequest.php',
            'UI/API/Requests/DeleteArticleRequest.php',
            'UI/API/Requests/ViewArticleRequest.php',
            'UI/API/Requests/ListArticlesRequest.php',
            'UI/API/Routes/v1/create_article.php',
            'UI/API/Routes/v1/update_article.php',
            'UI/API/Routes/v1/delete_article.php',
            'UI/API/Routes/v1/view_article.php',
            'UI/API/Routes/v1/list_articles.php',
            'UI/API/Tests/CreateArticleTest.php',
            'UI/API/Tests/UpdateArticleTest.php',
            'UI/API/Tests/DeleteArticleTest.php',
            'UI/API/Tests/ViewArticleTest.php',
            'UI/API/Tests/ListArticlesTest.php',
        ];
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        if ($this->finder->isDirectory(base_path('app/Modules/ModuleName'))) {
            $this->finder->deleteDirectory(base_path('app/Modules/ModuleName'));
        }
        if ($this->finder->isDirectory(base_path('app/Modules/Blog'))) {
            $this->finder->deleteDirectory(base_path('app/Modules/Blog'));
        }
        $this->activator->reset();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_module()
    {
        $code = $this->artisan('module:make', ['name' => 'Article']);

        $this->assertDirectoryExists($this->modulePath);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_folders()
    {
        $code = $this->artisan('module:make', ['name' => 'Article']);

        foreach (config('modules.generator.components') as $directory) {
            if ($directory['generate'] === true) {
                $this->assertDirectoryExists($this->modulePath . '/' . $directory['path']);
            }
        }
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_scaffold_files()
    {
        $code = $this->artisan('module:make', ['name' => 'Article']);

        $moduleJsonPath = $this->modulePath . '/module.json';
        $this->assertFileExists($moduleJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($moduleJsonPath));

        $composerJsonPath = $this->modulePath . '/composer.json';
        $this->assertFileExists($composerJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($composerJsonPath));

        $configPath = $this->modulePath . '/Config/article-module.php';
        $this->assertFileExists($configPath);
        $this->assertMatchesSnapshot($this->finder->get($configPath));

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_components()
    {
        $code = $this->artisan('module:make', ['name' => 'Article']);

        foreach ($this->moduleComponentPaths as $componentPath) {
            $path = $this->modulePath . '/' . $componentPath;
            $this->assertFileExists($path);
            $this->assertMatchesSnapshot($this->finder->get($path));
        }

        $migrationFiles = $this->finder->allFiles($this->modulePath . '/Data/Migrations');
        $this->assertCount(1, $migrationFiles);
        $this->assertMatchesSnapshot($migrationFiles[0]->getContents());

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_folder_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => 'ModuleName']);

        $this->assertTrue($this->finder->exists(base_path('app/Modules/ModuleName')));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_module_namespace_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => 'ModuleName']);

        $file = $this->finder->get(base_path('app/Modules/ModuleName') . '/Providers/ModuleNameServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_a_plain_module_with_no_components()
    {
        $code = $this->artisan('module:make', ['name' => 'ModuleName', '--plain' => true]);

        foreach ($this->moduleComponentPaths as $componentPath) {
            $path = $this->modulePath . '/' . $componentPath;
            $this->assertFileDoesNotExist($path);
        }
        $this->assertDirectoryDoesNotExist($this->modulePath . '/Data/Migrations');

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_plain_module_with_no_service_provider_in_modulejson_file()
    {
        $code = $this->artisan('module:make', ['name' => 'ModuleName', '--plain' => true]);

        $path = base_path('app/Modules/ModuleName') . '/module.json';
        $content = json_decode($this->finder->get($path));

        $this->assertCount(0, $content->providers);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_outputs_error_when_module_exists()
    {
        $this->artisan('module:make', ['name' => 'Article']);
        $code = $this->artisan('module:make', ['name' => 'Article']);

        $expected = 'Module [Article] already exist!
';
        $this->assertEquals($expected, Artisan::output());
        $this->assertSame(E_ERROR, $code);
    }

    /** @test */
    public function it_still_generates_module_if_it_exists_using_force_flag()
    {
        $this->artisan('module:make', ['name' => 'Article']);
        $code = $this->artisan('module:make', ['name' => 'Article', '--force' => true]);

        $output = Artisan::output();

        $notExpected = 'Module [Article] already exist!
';
        $this->assertNotEquals($notExpected, $output);
        $this->assertTrue(Str::contains($output, 'Module [Article] created successfully.'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_ignore_component_folders_to_generate()
    {
        $this->app['config']->set('modules.generator.components.seeder', ['path' => 'Data/Seeders', 'generate' => false]);
        $this->app['config']->set('modules.generator.components.provider', ['path' => 'Providers', 'generate' => false]);
        $this->app['config']->set('modules.generator.components.api-controller', ['path' => 'UI/API/Controllers', 'generate' => false]);

        $code = $this->artisan('module:make', ['name' => 'Article']);

        $this->assertDirectoryDoesNotExist($this->modulePath . '/Data/Seeders');
        $this->assertDirectoryDoesNotExist($this->modulePath . '/Providers');
        $this->assertDirectoryDoesNotExist($this->modulePath . '/UI/API/Controllers');
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_enabled_module()
    {
        $code = $this->artisan('module:make', ['name' => 'Article']);

        $this->assertTrue($this->repository->isEnabled('Article'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generates_disabled_module_with_disabled_flag()
    {
        $code = $this->artisan('module:make', ['name' => 'Article', '--disabled' => true]);

        $this->assertTrue($this->repository->isDisabled('Article'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generes_module_with_new_provider_location()
    {
        $this->app['config']->set('modules.generator.components.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => 'Article']);

        $this->assertDirectoryExists($this->modulePath . '/Base/Providers');

        $file = $this->finder->get($this->modulePath . '/module.json');
        $this->assertMatchesSnapshot($file);

        $file = $this->finder->get($this->modulePath . '/composer.json');
        $this->assertMatchesSnapshot($file);

        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_set_custom_model()
    {
        $code = $this->artisan('module:make', ['name' => 'Blog', '--entity' => 'Post']);

        $modulePath = base_path('app/Modules/Blog');
        $moduleComponentPaths = [
            'Actions/CreatePostAction.php',
            'Actions/UpdatePostAction.php',
            'Actions/DeletePostAction.php',
            'Actions/ViewPostAction.php',
            'Actions/ListPostsAction.php',
            'Data/Factories/PostFactory.php',
            'Data/Seeders/PostPermissionsSeeder_1.php',
            'DTO/CreatePostDTO.php',
            'Models/Post.php',
            'Policies/PostPolicy.php',
            'Providers/BlogServiceProvider.php',
            'Providers/RouteServiceProvider.php',
            'UI/API/QueryWizards/PostQueryWizard.php',
            'UI/API/QueryWizards/PostsQueryWizard.php',
            'UI/API/Resources/PostResource.php',
            'UI/API/Requests/CreatePostRequest.php',
            'UI/API/Requests/UpdatePostRequest.php',
            'UI/API/Requests/DeletePostRequest.php',
            'UI/API/Requests/ViewPostRequest.php',
            'UI/API/Requests/ListPostsRequest.php',
            'UI/API/Routes/v1/create_post.php',
            'UI/API/Routes/v1/update_post.php',
            'UI/API/Routes/v1/delete_post.php',
            'UI/API/Routes/v1/view_post.php',
            'UI/API/Routes/v1/list_posts.php',
            'UI/API/Tests/CreatePostTest.php',
            'UI/API/Tests/UpdatePostTest.php',
            'UI/API/Tests/DeletePostTest.php',
            'UI/API/Tests/ViewPostTest.php',
            'UI/API/Tests/ListPostsTest.php',
        ];

        $moduleJsonPath = $modulePath . '/module.json';
        $this->assertFileExists($moduleJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($moduleJsonPath));

        $composerJsonPath = $modulePath . '/composer.json';
        $this->assertFileExists($composerJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($composerJsonPath));

        $configPath = $modulePath . '/Config/blog-module.php';
        $this->assertFileExists($configPath);
        $this->assertMatchesSnapshot($this->finder->get($configPath));

        foreach ($moduleComponentPaths as $componentPath) {
            $path = $modulePath . '/' . $componentPath;
            $this->assertFileExists($path);
            $this->assertMatchesSnapshot($this->finder->get($path));
        }

        $migrationFiles = $this->finder->allFiles($modulePath . '/Data/Migrations');
        $this->assertCount(1, $migrationFiles);
        $this->assertMatchesSnapshot($migrationFiles[0]->getContents());

        $this->assertSame(0, $code);
    }
}
