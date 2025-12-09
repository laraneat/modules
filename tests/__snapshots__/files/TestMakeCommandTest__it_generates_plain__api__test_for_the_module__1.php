<?php

namespace Modules\Author\Tests\UI\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group author
 * @group api
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

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
