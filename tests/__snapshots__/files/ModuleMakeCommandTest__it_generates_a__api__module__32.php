<?php

namespace Modules\ArticleComment\Tests\UI\API;

use Modules\ArticleComment\Models\ArticleComment;
use Tests\TestCase;

/**
 * @group demo/article-comment
 * @group api
 */
class ListArticleCommentsTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'view-article-comment',
        'roles'       => '',
    ];

    public function test_list_article_comments(): void
    {
        $this->actingAsTestUser();

        ArticleComment::factory()->count(3)->create();

        $this->getJson(route('api.article_comments.list'))
            ->assertOk()
            ->assertJsonStructure([
                'links',
                'meta',
                'data'
            ])
            ->assertJsonCount(ArticleComment::query()->count(), 'data');
    }

    public function test_list_article_comments_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        ArticleComment::factory()->count(3)->create();

        $this->getJson(route('api.article_comments.list'))
            ->assertForbidden();
    }
}
