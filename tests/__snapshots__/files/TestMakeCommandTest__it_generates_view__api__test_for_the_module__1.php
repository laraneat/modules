<?php

namespace Modules\Author\Tests\UI\API;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group laraneat/author
 * @group api
 */
class ViewAuthorTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'view-author',
        'roles'       => '',
    ];

    public function test_view_author(): void
    {
        $this->actingAsTestUser();

        $author = Author::factory()->create();
        $expectedData = $author->toArray();

        $this->getJson(route('api.authors.view', ['author' => $author->getKey()]))
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->whereAll($expectedData)
                        ->etc()
                )
            );
    }

    public function test_view_author_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $author = Author::factory()->create();

        $this->getJson(route('api.authors.view', ['author' => $author->getKey()]))
            ->assertForbidden();
    }

    public function test_view_not_existing_author(): void
    {
        $this->actingAsTestUser();

        $this->getJson(route('api.authors.view', ['author' => 7777]))
            ->assertNotFound();
    }
}
