<?php

namespace Modules\Author\Tests\UI\API;

use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group laraneat/author
 * @group api
 */
class ListAuthorsTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'view-author',
        'roles'       => '',
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
            ->assertJsonCount(Author::query()->count(), 'data');
    }

    public function test_list_authors_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        Author::factory()->count(3)->create();

        $this->getJson(route('api.authors.list'))
            ->assertForbidden();
    }
}
