<?php

namespace Modules\Author\Tests\UI\WEB;

use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group author
 * @group web
 */
class DeleteAuthorTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'delete-author',
        'roles' => '',
    ];

    public function test_delete_author(): void
    {
        $this->actingAsTestUser();

        $author = Author::factory()->create();

        $this->delete(route('web.authors.delete', ['author' => $author->getKey()]))
            ->assertNoContent();

        $this->assertNull(Author::find($author->getKey()));
    }

    public function test_delete_author_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $author = Author::factory()->create();

        $this->delete(route('web.authors.delete', ['author' => $author->getKey()]))
            ->assertForbidden();
    }

    public function test_delete_not_existing_author(): void
    {
        $this->actingAsTestUser();

        $this->delete(route('web.authors.delete', ['author' => 7777]))
            ->assertNotFound();
    }
}
