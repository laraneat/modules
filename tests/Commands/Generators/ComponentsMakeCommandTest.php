<?php

namespace Laraneat\Modules\Tests\Commands\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laraneat\Modules\Contracts\ActivatorInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Module;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @group command
 * @group generator
 */
class ComponentsMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private string $modulePath;
    private ActivatorInterface $activator;
    private RepositoryInterface $repository;
    private array $moduleComponentPaths;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => 'Article', '--plain' => true]);
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

    protected function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
        if ($this->finder->isDirectory(base_path('app/Modules/Blog'))) {
            $this->finder->deleteDirectory(base_path('app/Modules/Blog'));
        }
        $this->activator->reset();

        parent::tearDown();
    }

    /** @test */
    public function it_generates_module_components()
    {
        $code = $this->artisan('module:make:components', [
            'name' => 'Article',
        ]);

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
    public function it_can_set_custom_model()
    {
        $this->artisan('module:make', ['name' => 'Blog', '--entity' => 'Post']);
        $this->artisan('module:make:components', ['name' => 'Blog', '--entity' => 'Comment']);

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

            'Actions/CreateCommentAction.php',
            'Actions/UpdateCommentAction.php',
            'Actions/DeleteCommentAction.php',
            'Actions/ViewCommentAction.php',
            'Actions/ListCommentsAction.php',
            'Data/Factories/CommentFactory.php',
            'Data/Seeders/CommentPermissionsSeeder_1.php',
            'DTO/CreateCommentDTO.php',
            'Models/Comment.php',
            'Policies/CommentPolicy.php',
            'Providers/BlogServiceProvider.php',
            'Providers/RouteServiceProvider.php',
            'UI/API/QueryWizards/CommentQueryWizard.php',
            'UI/API/QueryWizards/CommentsQueryWizard.php',
            'UI/API/Resources/CommentResource.php',
            'UI/API/Requests/CreateCommentRequest.php',
            'UI/API/Requests/UpdateCommentRequest.php',
            'UI/API/Requests/DeleteCommentRequest.php',
            'UI/API/Requests/ViewCommentRequest.php',
            'UI/API/Requests/ListCommentsRequest.php',
            'UI/API/Routes/v1/create_comment.php',
            'UI/API/Routes/v1/update_comment.php',
            'UI/API/Routes/v1/delete_comment.php',
            'UI/API/Routes/v1/view_comment.php',
            'UI/API/Routes/v1/list_comments.php',
            'UI/API/Tests/CreateCommentTest.php',
            'UI/API/Tests/UpdateCommentTest.php',
            'UI/API/Tests/DeleteCommentTest.php',
            'UI/API/Tests/ViewCommentTest.php',
            'UI/API/Tests/ListCommentsTest.php',
        ];

        $moduleJsonPath = $modulePath . '/module.json';
        $this->assertFileExists($moduleJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($moduleJsonPath));

        $composerJsonPath = $modulePath . '/composer.json';
        $this->assertFileExists($composerJsonPath);
        $this->assertMatchesSnapshot($this->finder->get($composerJsonPath));

        foreach ($moduleComponentPaths as $componentPath) {
            $path = $modulePath . '/' . $componentPath;
            $this->assertFileExists($path);
            $this->assertMatchesSnapshot($this->finder->get($path));
        }

        $migrationFiles = $this->finder->allFiles($modulePath . '/Data/Migrations');
        $this->assertCount(2, $migrationFiles);
        $this->assertMatchesSnapshot($migrationFiles[0]->getContents());
        $this->assertMatchesSnapshot($migrationFiles[1]->getContents());
    }
}
