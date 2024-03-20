<?php

namespace Modules\ArticleComment\Tests\UI\API;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\ArticleComment\Models\ArticleComment;
use Tests\TestCase;

/**
 * @group article-comment
 * @group api
 */
class CreateArticleCommentTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'create-article-comment',
        'roles' => '',
    ];

    public function test_create_article_comment(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->postJson(route('api.article_comments.create'), $data)
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->has('id')
                        ->whereAll($data)
                        ->etc()
                    )
            );

        $this->assertDatabaseHas(ArticleComment::class, $data);
    }

    public function test_create_article_comment_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $data = $this->getTestData();

        $this->postJson(route('api.article_comments.create'), $data)
            ->assertForbidden();
    }

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }
}
