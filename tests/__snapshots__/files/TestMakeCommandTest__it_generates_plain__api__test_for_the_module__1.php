<?php

namespace Modules\Author\Tests\UI\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('author')]
#[Group('api')]
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
