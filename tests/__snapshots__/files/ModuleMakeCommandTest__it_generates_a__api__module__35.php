<?php

namespace Modules\ArticleComment\Tests\UI\API;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\ArticleComment\Models\ArticleComment;
use Tests\TestCase;

/**
 * @group article-comment
 * @group api
 */
class ViewArticleCommentTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'view-article-comment',
        'roles' => '',
    ];

    public function test_view_article_comment(): void
    {
        $this->actingAsTestUser();

        $articleComment = ArticleComment::factory()->create();
        $expectedData = $articleComment->toArray();

        $this->getJson(route('api.article_comments.view', ['articleComment' => $articleComment->getKey()]))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->whereAll($expectedData)
                        ->etc()
                )
            );
    }

    public function test_view_article_comment_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $articleComment = ArticleComment::factory()->create();

        $this->getJson(route('api.article_comments.view', ['articleComment' => $articleComment->getKey()]))
            ->assertForbidden();
    }

    public function test_view_not_existing_article_comment(): void
    {
        $this->actingAsTestUser();

        $this->getJson(route('api.article_comments.view', ['articleComment' => 7777]))
            ->assertNotFound();
    }
}
