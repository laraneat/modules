<?php

namespace Modules\ArticleComment\Tests\UI\API;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\ArticleComment\Models\ArticleComment;
use Tests\TestCase;

/**
 * @group article-comment
 * @group api
 */
class UpdateArticleCommentTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'update-article-comment',
        'roles' => '',
    ];

    public function test_update_article_comment(): void
    {
        $this->actingAsTestUser();

        $articleComment = ArticleComment::factory()->create();

        $data = $this->getTestData();
        $expectedData = array_merge($data, [
            'id' => $articleComment->getKey(),
        ]);

        $this->patchJson(route('api.article_comments.update', ['articleComment' => $articleComment->getKey()]), $data)
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->whereAll($expectedData)
                        ->etc()
                )
            );

        $this->assertDatabaseHas(ArticleComment::class, $expectedData);
    }

    public function test_update_article_comment_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $articleComment = ArticleComment::factory()->create();

        $data = $this->getTestData();

        $this->patchJson(route('api.article_comments.update', ['articleComment' => $articleComment->getKey()]), $data)
            ->assertForbidden();
    }

    public function test_update_non_existing_article_comment(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->patchJson(route('api.article_comments.update', ['articleComment' => 7777]), $data)
            ->assertNotFound();
    }

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }
}
