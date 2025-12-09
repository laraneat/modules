<?php

namespace Modules\Author\Tests\UI\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group author
 * @group api
 */
class ListAuthorsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'view-author',
        'roles' => '',
    ];

    public function test_list_authors(): void
    {
        $this->actingAsTestUser();

        Author::factory()->count(3)->create();

        $this->getJson(route('api.authors.list'))
            ->assertOk()
            ->assertJsonStructure([
                'links',
                'meta',
                'data'
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_list_authors_unauthenticated(): void
    {
        Author::factory()->count(3)->create();

        $this->getJson(route('api.authors.list'))
            ->assertUnauthorized();
    }

    public function test_list_authors_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        Author::factory()->count(3)->create();

        $this->getJson(route('api.authors.list'))
            ->assertForbidden();
    }
}
