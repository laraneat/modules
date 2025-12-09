<?php

namespace Modules\Author\Tests\UI\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group author
 * @group api
 */
class UpdateAuthorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'update-author',
        'roles' => '',
    ];

    public function test_update_author(): void
    {
        $this->actingAsTestUser();

        $author = Author::factory()->create();

        $data = $this->getTestData();
        $expectedData = array_merge($data, [
            'id' => $author->getKey(),
        ]);

        $this->patchJson(route('api.authors.update', ['author' => $author->getKey()]), $data)
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->whereAll($expectedData)
                        ->etc()
                )
            );

        $this->assertDatabaseHas(Author::class, $expectedData);
    }

    public function test_update_author_with_invalid_data(): void
    {
        $this->actingAsTestUser();

        $author = Author::factory()->create();

        $this->patchJson(route('api.authors.update', ['author' => $author->getKey()]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                // TODO: add expected validation errors here
            ]);
    }

    public function test_update_author_unauthenticated(): void
    {
        $author = Author::factory()->create();

        $data = $this->getTestData();

        $this->patchJson(route('api.authors.update', ['author' => $author->getKey()]), $data)
            ->assertUnauthorized();
    }

    public function test_update_author_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $author = Author::factory()->create();

        $data = $this->getTestData();

        $this->patchJson(route('api.authors.update', ['author' => $author->getKey()]), $data)
            ->assertForbidden();
    }

    public function test_update_non_existing_author(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->patchJson(route('api.authors.update', ['author' => 7777]), $data)
            ->assertNotFound();
    }

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }
}
