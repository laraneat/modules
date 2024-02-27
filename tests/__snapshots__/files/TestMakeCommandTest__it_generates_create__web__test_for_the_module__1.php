<?php

namespace Modules\Author\Tests\UI\WEB;

use Modules\Author\Models\Author;
use Tests\TestCase;

/**
 * @group laraneat/author
 * @group web
 */
class CreateAuthorTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => 'create-author',
        'roles'       => '',
    ];

    protected function getTestData(array $mergeData = []): array
    {
        return array_merge([
            // TODO: add fields here
        ], $mergeData);
    }

    public function test_create_author(): void
    {
        $this->actingAsTestUser();

        $data = $this->getTestData();

        $this->post(route('web.authors.create'), $data)
            ->assertCreated();

        $this->assertDatabaseHas(Author::class, $data);
    }

    public function test_create_author_without_access(): void
    {
        $this->actingAsTestUserWithoutAccess();

        $data = $this->getTestData();

        $this->post(route('web.authors.create'), $data)
            ->assertForbidden();
    }
}
