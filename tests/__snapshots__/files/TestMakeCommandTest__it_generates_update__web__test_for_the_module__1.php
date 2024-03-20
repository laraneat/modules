<?php

namespace Modules\Author\Tests\UI\WEB;

use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group author
 * @group web
 */
class UpdateAuthorTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'update-author',
        'roles' => '',
    ];

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }

    public function test_update_author(): void
    {
        $this->actingAsTestUser();

        $author = Author::factory()->create();

        $data = $this->getTestData();
        $expectedData = array_merge($data, [
            'id' => $author->getKey(),
        ]);

        $this->patch(route('web.authors.update', ['author' => $author->getKey()]), $data)
           ->assertOk();

        $this->assertDatabaseHas(Author::class, $expectedData);
    }

    public function test_update_authorWithoutAccess(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $author = Author::factory()->create();

        $data = $this->getTestData();

        $this->patch(route('web.authors.update', ['author' => $author->getKey()]), $data)
            ->assertForbidden();
    }

    public function test_update_non_existing_author(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->patch(route('web.authors.update', ['author' => 7777]), $data)
            ->assertNotFound();
    }
}
