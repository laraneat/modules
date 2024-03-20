<?php

namespace Modules\Author\Tests\UI\API;

use Tests\TestCase;

/**
 * @group author
 * @group api
 */
class ExampleTest extends TestCase
{
    /**
     * Roles and permissions, to be attached on the user by default
     */
    protected array $testUserAccess = [
        'permissions' => '',
        'roles' => '',
    ];

    public function test(): void
    {
        //
    }
}
