<?php

namespace Modules\ArticleComment\Tests\UI\API;

use Modules\ArticleComment\Models\ArticleComment;
use Tests\TestCase;

/**
 * @group demo/article-comment
 * @group api
 */
class DeleteArticleCommentTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'delete-article-comment',
        'roles'       => '',
    ];

    public function test_delete_article_comment(): void
    {
        $this->actingAsTestUser();

        $articleComment = ArticleComment::factory()->create();

        $this->deleteJson(route('api.article_comments.delete', ['articleComment' => $articleComment->getKey()]))
            ->assertNoContent();

        $this->assertNull(ArticleComment::find($articleComment->getKey()));
    }

    public function test_delete_article_comment_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $articleComment = ArticleComment::factory()->create();

        $this->deleteJson(route('api.article_comments.delete', ['articleComment' => $articleComment->getKey()]))
            ->assertForbidden();
    }

    public function test_delete_not_existing_article_comment(): void
    {
        $this->actingAsTestUser();

        $this->deleteJson(route('api.article_comments.delete', ['articleComment' => 7777]))
            ->assertNotFound();
    }
}
