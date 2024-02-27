<?php

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/article'),
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/author'),
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/Foo'),
    ], $this->app->basePath('/modules'));
    $this->setVendorModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/vendor/laraneat/foo'),
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/vendor/laraneat/bar'),
    ]);
});

it('generates a "plain" module', function () {
    $this->artisan('module:make', [
        'name' => 'ArticleComment',
        '--preset' => 'plain',
        '--entity' => 'ArticleComment',
    ])
        ->assertSuccessful();

    $paths = [
        'Actions/CreateArticleCommentAction.php',
        'Actions/UpdateArticleCommentAction.php',
        'Actions/DeleteArticleCommentAction.php',
        'Actions/ViewArticleCommentAction.php',
        'Actions/ListArticleCommentsAction.php',
        'Data/Factories/ArticleCommentFactory.php',
        'Data/Seeders/ArticleCommentPermissionsSeeder_1.php',
        'DTO/CreateArticleCommentDTO.php',
        'Models/ArticleComment.php',
        'Policies/ArticleCommentPolicy.php',
        'Providers/BlogServiceProvider.php',
        'Providers/RouteServiceProvider.php',
        'UI/API/QueryWizards/ArticleCommentQueryWizard.php',
        'UI/API/QueryWizards/ArticleCommentsQueryWizard.php',
        'UI/API/Resources/ArticleCommentResource.php',
        'UI/API/Requests/CreateArticleCommentRequest.php',
        'UI/API/Requests/UpdateArticleCommentRequest.php',
        'UI/API/Requests/DeleteArticleCommentRequest.php',
        'UI/API/Requests/ViewArticleCommentRequest.php',
        'UI/API/Requests/ListArticleCommentsRequest.php',
        'UI/API/Routes/v1/create_article_comment.php',
        'UI/API/Routes/v1/update_article_comment.php',
        'UI/API/Routes/v1/delete_article_comment.php',
        'UI/API/Routes/v1/view_article_comment.php',
        'UI/API/Routes/v1/list_article_comments.php',
        'UI/API/Tests/CreateArticleCommentTest.php',
        'UI/API/Tests/UpdateArticleCommentTest.php',
        'UI/API/Tests/DeleteArticleCommentTest.php',
        'UI/API/Tests/ViewArticleCommentTest.php',
        'UI/API/Tests/ListArticleCommentsTest.php',
    ];
    foreach($paths as $filePath) {
        assertFileExists($filePath);
        assertMatchesFileSnapshot($filePath);
    }
});

it('generates a "base" module', function () {

});

it('generates a "api" module', function () {

});

it('displays an error message if the passed module name is not valid', function () {

});

it('displays an error message when a module with the same package name already exists', function () {

});

it('automatically converts module name to studly case', function () {

});
