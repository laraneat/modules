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
class CreateAuthorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'create-author',
        'roles' => '',
    ];

    public function test_create_author(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->postJson(route('api.authors.create'), $data)
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->has('id')
                        ->whereAll($data)
                        ->etc()
                )
            );

        $this->assertDatabaseHas(Author::class, $data);
    }

    public function test_create_author_with_invalid_data(): void
    {
        $this->actingAsTestUser();

        $this->postJson(route('api.authors.create'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                // TODO: add expected validation errors here
            ]);
    }

    public function test_create_author_unauthenticated(): void
    {
        $data = $this->getTestData();

        $this->postJson(route('api.authors.create'), $data)
            ->assertUnauthorized();
    }

    public function test_create_author_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $data = $this->getTestData();

        $this->postJson(route('api.authors.create'), $data)
            ->assertForbidden();
    }

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }
}
