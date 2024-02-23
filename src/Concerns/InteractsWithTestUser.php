<?php

namespace Laraneat\Modules\Concerns;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * @template U
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait InteractsWithTestUser
{
    /**
     * @var class-string<U>|null
     */
    protected ?string $testUserClass = null;

    /**
     * Roles and permissions, to be attached on the user by default
     *
     * @var array{permissions?: string|array<string>, roles?:string|array<string>}
     */
    protected array $testUserAccess = [
        'permissions' => '',
        'roles' => '',
    ];

    protected ?string $testUserGuard = null;

    /**
     * Create a test user without access and make him logged into the application.
     * Same as `besTestUser()` but always overrides the user $access
     * (roles and permissions) with null. So the user can be used to test
     * if unauthorized user tried to access your protected endpoint.
     *
     * @param array|null $userDetails
     * @param string|null $guard
     *
     * @return $this
     */
    public function beTestUserWithoutAccess(?array $userDetails = null, ?string $guard = null): static
    {
        return $this->actingAsTestUserWithoutAccess($userDetails, $guard);
    }

    /**
     * Create a test user and make him logged into the application.
     *
     * @param array|null $userDetails
     * @param array{permissions?: string|array<string>, roles?:string|array<string>}|null $access
     * @param string|null $guard
     *
     * @return $this
     */
    public function beTestUser(?array $userDetails = null, ?array $access = null, ?string $guard = null): static
    {
        return $this->actingAsTestUser($userDetails, $access, $guard);
    }

    /**
     * Create a test user without access and make him logged into the application.
     * Same as `actingAsTestUser()` but always overrides the user $access
     * (roles and permissions) with null. So the user can be used to test
     * if unauthorized user tried to access your protected endpoint.
     *
     * @param array|null $userDetails
     * @param string|null $guard
     *
     * @return $this
     */
    public function actingAsTestUserWithoutAccess(?array $userDetails = null, ?string $guard = null): static
    {
        return $this->actingAs(
            $this->createTestUserWithoutAccess($userDetails),
            $guard ?? $this->testUserGuard
        );
    }

    /**
     * Create a test user and make him logged into the application.
     *
     * @param array|null $userDetails
     * @param array{permissions?: string|array<string>, roles?:string|array<string>}|null $access
     * @param string|null $guard
     *
     * @return $this
     */
    public function actingAsTestUser(?array $userDetails = null, ?array $access = null, ?string $guard = null): static
    {
        return $this->actingAs(
            $this->createTestUser($userDetails, $access),
            $guard ?? $this->testUserGuard
        );
    }

    /**
     * Create test user without access.
     * Same as `createTestUser()` but always overrides the user $access
     * (roles and permissions) with null. So the user can be used to test
     * if unauthorized user tried to access your protected endpoint.
     *
     * @param array|null $userDetails
     * @return U
     */
    public function createTestUserWithoutAccess(?array $userDetails = null): UserContract
    {
        return $this->createTestUser($userDetails, [
            'permissions' => null,
            'roles' => null,
        ]);
    }

    /**
     * Create test user.
     * By default, Users will be given the Roles and Permissions found in the class
     * `$access` property. But the $access parameter can be used to override the
     * defined roles and permissions in the `$access` property of your class.
     *
     * @param array|null $userDetails
     * @param array{permissions?: string|array<string>, roles?:string|array<string>}|null $access
     * @return U
     */
    public function createTestUser(?array $userDetails = null, ?array $access = null): UserContract
    {
        $this->testUserClass = $this->testUserClass ?? config('modules.generator.user_model');

        if (! $this->testUserClass) {
            throw new LogicException("User class was not provided!");
        }

        return $this->setupTestUserAccess(
            $this->factoryCreateUser($userDetails),
            $access
        );
    }

    /**
     * @param array|null $userDetails
     * @return U
     */
    private function factoryCreateUser(?array $userDetails = null): UserContract
    {
        if (!method_exists($this->testUserClass, 'factory')) {
            throw new LogicException("class `$this->testUserClass` does not have method `factory()`");
        }

        return $this->testUserClass::factory()->create($this->prepareUserDetails($userDetails));
    }

    private function prepareUserDetails(?array $userDetails = null): array
    {
        $defaultUserDetails = [
            'name' => 'Testing user',
            'email' => 'testing@test.com',
            'password' => 'testing-password',
        ];
        $userDetails = $userDetails ? array_merge($defaultUserDetails, $userDetails) : $defaultUserDetails;

        $userDetails['password'] = Hash::make($userDetails['password']);

        return $userDetails;
    }

    private function setupTestUserAccess(UserContract $user, ?array $access = null): UserContract
    {
        $access = $access ?: $this->testUserAccess;

        if ($access['permissions'] ?? false) {
            if (!method_exists($user, 'givePermissionTo')) {
                throw new LogicException("user instance does not have method `givePermissionTo()`, make sure the user class uses `spatie/laravel-permission`");
            }
            $user->givePermissionTo($access['permissions']);
        }

        if ($access['roles'] ?? false) {
            if (!method_exists($user, 'assignRole')) {
                throw new LogicException("user instance does not have method `assignRole()`, make sure the user class uses `spatie/laravel-permission`");
            }
            $user->assignRole($access['roles']);
        }

        return $user;
    }
}
