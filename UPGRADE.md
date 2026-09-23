# Upgrade Guide

## From 2.x to 3.0

Version 3 is a smaller package: it loads modules by convention and adds `--module` to the Laravel generators.
The component generators, stubs, migration commands and test helpers of 2.x are gone.

The steps below are written so that a developer or an AI agent can follow them one by one. Commit after
every step, and run the checks at the end.

### Before you start

- The application runs Laravel 13.12 or newer with the latest `laraneat/modules` 2.x, and PHP 8.3 or newer.
- The test suite passes.
- Save the routes of the application to compare them later:

  ```bash
  php artisan route:list --json \
    | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;' \
    > /tmp/routes-before.json
  ```

### 1. Update the package

```bash
composer require laraneat/modules:^3.0 --no-update
composer update laraneat/modules --with-all-dependencies --no-scripts
```

Artisan does not work again until [step 8](#8-reinstall-the-modules-and-sync-them): the old config and
the module providers use classes that are removed. Steps 2 to 7 only edit files.

`composer/composer` is no longer a dependency of the package. Keep it only if the application uses it.

Delete the manifest cache of 2.x, it is not used anymore:

```bash
rm -f bootstrap/cache/laraneat-modules.php
```

### 2. Update the config

Copy the new config next to the old one and move your values into it:

```bash
mv config/modules.php config/modules.php.v2
cp vendor/laraneat/modules/config/modules.php config/modules.php
```

Laravel loads only the `.php` files of `config/`, so the old file, which references removed classes, is not
loaded anymore.

| 2.x key                          | 3.0                                                                          |
|----------------------------------|------------------------------------------------------------------------------|
| `path`, `namespace`              | the same keys                                                                |
| `composer.vendor`                | `vendor`                                                                     |
| `components.*` with a namespace  | `generators`, see below                                                      |
| `components.api-route`, `web-route` | `routes`, see [step 4](#4-move-the-routes-into-route-groups)              |
| `components` without a namespace (`config`, `lang`, `migration`, `view`) | fixed: `config`, `lang`, `database/migrations`, `resources/views` |
| `custom_stubs`                   | removed, see [step 9](#9-replace-the-generators)                              |
| `composer.author`                | removed: put authors in the module templates                                 |
| `user_model`, `create_permission` | removed, see [step 6](#6-copy-the-test-and-response-helpers-into-the-application) |
| `cache.enabled`                  | removed: the cache is built by `php artisan optimize`                        |

`generators` maps a generator command to a namespace inside the module. It replaces the `namespace` of the
2.x components. Map only the commands whose namespace differs from the Laravel one. For the default 2.x
layout:

```php
'generators' => [
    'make:controller' => 'UI\API\Controllers',
    'make:request' => 'UI\API\Requests',
    'make:resource' => 'UI\API\Resources',
    'make:command' => 'UI\CLI\Commands',
    'make:data' => 'DTO',
    'make:mail' => 'Mails',
    'make:middleware' => 'Middleware',
],
```

One command has one namespace. 2.x had separate API and WEB controllers and requests: generate the other
kind with a fully qualified name, for example
`make:controller "Modules\Blog\UI\WEB\Controllers\PostController" --module=blog`. The namespaces also apply
to the files that options such as `make:model -a` create.

The `make:command` namespace is also where module commands are discovered, so it must match the directory
of the existing commands.

Delete `config/modules.php.v2` when everything is moved.

### 3. Remove the module service providers that only load resources

The package now loads the resources of every module. For each module in `modules/*`:

1. Find its providers in `extra.laravel.providers` of `modules/<module>/composer.json`.
2. A provider that extends `Laraneat\Modules\Support\ModuleServiceProvider`:
   - Remove the `loadConfigurations()`, `loadMigrations()`, `loadCommands()`, `loadTranslations()` and
     `loadViews()` methods and their calls.
   - If nothing else is left, delete the provider and remove it from `extra.laravel.providers`.
   - Otherwise extend `Illuminate\Support\ServiceProvider` instead. `loadCommandsFrom()`, `loadFiles()`,
     `loadAllFiles()` and `getPublishableViewPaths()` do not exist anymore.
3. Delete the route service provider that uses `Laraneat\Modules\Support\Concerns\CanLoadRoutesFromDirectory`
   and remove it from `extra.laravel.providers`. Keep anything it does besides loading routes (route
   patterns, model bindings, rate limiters) in another provider.

What 3.0 loads, and how it differs from the 2.x providers:

- **Config**: every `config/*.php` file of the module, keyed by its file name. In 2.x only
  `config/<module>.php` was merged, and only when `loadConfigurations()` was called. Check that the other
  files of `config/` are meant to be config. The application config still wins; module config files are
  not publishable anymore.
- **Views and translations**: the namespace is the module directory name (`blog::`). In 2.x it was the kebab
  case name of the module; rename the directory or the references if they differ. Views published into
  `resources/views/modules/<module>` or `resources/views/vendor/<module>` do not override the module views
  anymore: move the changes into the module. Translation overrides in `lang/vendor/<module>` still work.
- **Migrations and commands**: registered only in the console. 2.x registered migrations for HTTP requests
  too: `Artisan::call('migrate')` during an HTTP request does not see the module migrations anymore.

Search for leftovers:

```bash
grep -rn "Laraneat\\\\Modules\\\\Support\\\\ModuleServiceProvider\|CanLoadRoutesFromDirectory" app modules tests
```

### 4. Move the routes into route groups

Module routes are loaded by the route groups of `config/modules.php`. For the default 2.x layout:

```php
'routes' => [
    'api' => ['path' => 'src/UI/API/routes', 'prefix' => 'api', 'middleware' => ['api']],
    'web' => ['path' => 'src/UI/WEB/routes', 'middleware' => ['web']],
],
```

Use the prefix, middleware and other attributes of the deleted route service providers. A module whose routes
do not fit the groups loads them in its own service provider with `Route::group()`.

Two differences from `CanLoadRoutesFromDirectory`:

- Nested directories are appended to the prefix. In 2.x, `routes/v1/admin/stats.php` got the `api/admin`
  prefix; now it gets `api/v1/admin`. Routes one directory deep keep their URIs.
- The files of a directory are loaded before its subdirectories (2.x loaded subdirectories first), and
  groups are loaded for all modules in turn: first every `api` route, then every `web` route.
- Route files are loaded while the package boots, before the providers of the application boot. 2.x loaded
  them after the module route service provider booted. `Route::pattern()` and route macros defined in
  `AppServiceProvider::boot()` do not reach module routes anymore: define them in the `register()` method
  of a provider, or use `->where()` in the route files.

When two routes can match the same URL (`posts/{post}` and `posts/export`), make sure the specific one is
still registered first.

### 5. Replace the module seeders trait

`Laraneat\Modules\Support\Concerns\CanRunModuleSeeders` is replaced by `Modules::seeders()`:

```php
// Before
use CanRunModuleSeeders;

$this->runSeedersFromModules();
$this->runSeedersFromModules('Deployment');
$this->runSeedersFromModules(['Deployment', 'Demo']);

// After
use Laraneat\Modules\Facades\Modules;

$this->call(Modules::seeders());
$this->call(Modules::seeders('', 'Deployment'));
$this->call(Modules::seeders('', 'Deployment', 'Demo'));
```

`runSeedersFromModules($directories)` always included `database/seeders`; with `Modules::seeders()`, `''` is
`database/seeders` and you list it yourself. The order is the same: by the `_N` class name suffix, then by
class name. Differences:

- Abstract classes and classes that are not seeders are skipped.
- Only direct subdirectories of `database/seeders` are read: `Modules::seeders('Demo/Extra')` returns nothing.
- Class names come from the file paths, not from the namespace in the file: a seeder whose namespace does
  not match its path is not found.

If you overrode methods of the trait (`sortSeederClasses()`, `getSeederClassesFromModule()`), apply the same
logic to the array that `Modules::seeders()` returns.

### 6. Copy the test and response helpers into the application

`InteractsWithTestUser` and `WithJsonResponseHelpers` were not related to modules and are removed. Copy them
into the application:

<details>
<summary><code>tests/Concerns/InteractsWithTestUser.php</code></summary>

```php
<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Creates users and logs them in. Roles and permissions need spatie/laravel-permission.
 *
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait InteractsWithTestUser
{
    /**
     * The user model; the model of the "users" auth provider by default.
     *
     * @var class-string|null
     */
    protected ?string $testUserClass = null;

    /**
     * Roles and permissions of test users, unless a test passes its own.
     *
     * @var array{permissions?: string|array<string>|null, roles?: string|array<string>|null}
     */
    protected array $testUserAccess = [
        'permissions' => '',
        'roles' => '',
    ];

    protected ?string $testUserGuard = null;

    public function beTestUserWithoutAccess(?array $userDetails = null, ?string $guard = null): static
    {
        return $this->actingAsTestUserWithoutAccess($userDetails, $guard);
    }

    /**
     * @param  array{permissions?: string|array<string>|null, roles?: string|array<string>|null}|null  $access
     */
    public function beTestUser(?array $userDetails = null, ?array $access = null, ?string $guard = null): static
    {
        return $this->actingAsTestUser($userDetails, $access, $guard);
    }

    public function actingAsTestUserWithoutAccess(?array $userDetails = null, ?string $guard = null): static
    {
        return $this->actingAs($this->createTestUserWithoutAccess($userDetails), $guard ?? $this->testUserGuard);
    }

    /**
     * @param  array{permissions?: string|array<string>|null, roles?: string|array<string>|null}|null  $access
     */
    public function actingAsTestUser(?array $userDetails = null, ?array $access = null, ?string $guard = null): static
    {
        return $this->actingAs($this->createTestUser($userDetails, $access), $guard ?? $this->testUserGuard);
    }

    public function createTestUserWithoutAccess(?array $userDetails = null): Authenticatable
    {
        return $this->createTestUser($userDetails, []);
    }

    /**
     * $access replaces $testUserAccess: null gives the default access, [] gives none.
     *
     * @param  array{permissions?: string|array<string>|null, roles?: string|array<string>|null}|null  $access
     */
    public function createTestUser(?array $userDetails = null, ?array $access = null): Authenticatable
    {
        $class = $this->testUserClass ?? config('auth.providers.users.model');

        if (! is_string($class) || ! method_exists($class, 'factory')) {
            throw new LogicException('Set $testUserClass to a user model that has a factory.');
        }

        $user = $class::factory()->create($this->testUserAttributes($userDetails ?? []));
        $access ??= $this->testUserAccess;

        if (! empty($access['permissions'])) {
            if (! method_exists($user, 'givePermissionTo')) {
                throw new LogicException('The user model must use spatie/laravel-permission to give permissions.');
            }

            $user->givePermissionTo($access['permissions']);
        }

        if (! empty($access['roles'])) {
            if (! method_exists($user, 'assignRole')) {
                throw new LogicException('The user model must use spatie/laravel-permission to assign roles.');
            }

            $user->assignRole($access['roles']);
        }

        return $user;
    }

    /**
     * The attributes of a test user. Override it when the user model has other columns.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    protected function testUserAttributes(array $details): array
    {
        $attributes = [
            'name' => 'Testing user',
            'email' => 'testing@test.com',
            'password' => 'testing-password',
            ...$details,
        ];

        $attributes['password'] = Hash::make($attributes['password']);

        return $attributes;
    }
}
```

</details>

<details>
<summary><code>app/Support/Concerns/WithJsonResponseHelpers.php</code></summary>

```php
<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Illuminate\Http\JsonResponse;

trait WithJsonResponseHelpers
{
    public function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function created(mixed $data = null, int $status = 201, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function deleted(mixed $data = null, int $status = 204, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function accepted(mixed $data = null, int $status = 202, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function noContent(int $status = 204, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse(null, $status, $headers, $options);
    }
}
```

</details>

Then replace the imports. The calls stay the same:

```bash
grep -rl --null "Laraneat\\\\Modules\\\\Support\\\\Concerns\\\\\(InteractsWithTestUser\|WithJsonResponseHelpers\)" app modules tests \
  | xargs -0 perl -pi -e 's/Laraneat\\Modules\\Support\\Concerns\\InteractsWithTestUser/Tests\\Concerns\\InteractsWithTestUser/g; s/Laraneat\\Modules\\Support\\Concerns\\WithJsonResponseHelpers/App\\Support\\Concerns\\WithJsonResponseHelpers/g'
```

Two changes in `InteractsWithTestUser`:

- The user model comes from `$testUserClass` or the `users` auth provider, not from `modules.user_model`.
- An explicit empty access now creates a user without access: `actingAsTestUser(null, [])` is the same as
  `actingAsTestUserWithoutAccess()`. In 2.x it gave the default access. Find such calls with
  `grep -rn "TestUser([^)]*\[\])" modules tests`.

### 7. Update the code that uses the package API

- The facade is `Laraneat\Modules\Facades\Modules` (was `Laraneat\Modules\Support\Facades\Modules`). The
  global `Modules` alias points to the new facade.
- `Laraneat\Modules\ModulesRepository` is `Laraneat\Modules\ModuleRepository`. The 2.x exceptions and the
  classes of `Laraneat\Modules\Providers` are removed; the exceptions of 3.0 extend
  `Laraneat\Modules\Exceptions\ModulesException`.
- Modules are identified by their directory name, not by the package name.

| 2.x                                        | 3.0                                           |
|--------------------------------------------|-----------------------------------------------|
| `Modules::getModules()`                    | `Modules::all()` (keyed by the directory name) |
| `Modules::find('app/blog')`                | `Modules::find('blog')`                        |
| `Modules::findOrFail('app/blog')`          | `Modules::get('blog')`                         |
| `Modules::has('app/blog')`                 | `Modules::find('blog') !== null`              |
| `Modules::count()`                         | `count(Modules::all())`                        |
| `Modules::filterByName('blog')`, `filterByNameOrFail()` | `Modules::find('blog')`, `Modules::get('blog')` |
| `Modules::getScanPaths()`, `addScanPath()` | the `path` config key: one directory of modules |
| `Modules::toArray()`, `$module->toArray()` | `Modules::all()`, the public properties of `Module` |
| `Modules::delete()`, `syncWithComposer()`  | `php artisan module:delete`, `module:sync`     |
| `Modules::buildModulesManifest()`, `pruneModulesManifest()` | `php artisan module:cache`, `module:clear` |
| `$module->getName()`, `getPackageName()`, `getNamespace()`, `getPath()` | `$module->name`, `->package`, `->namespace`, `->path` |
| `$module->subPath('src')`, `subNamespace('Models')` | `$module->path.'/src'`, `$module->namespace.'\\Models'` |
| `$module->getStudlyName()`, `getKebabName()`, `getSnakeName()` | `Str::studly($module->name)`, `$module->name`, `Str::snake(Str::camel($module->name))` |
| `$module->getProviders()`, `getAliases()`  | `extra.laravel` of the module `composer.json`  |

```bash
grep -rnE 'Laraneat\\Modules\\(Support|Enums|Exceptions|Providers|ModulesRepository|Module;)|Modules::' \
  app modules tests database routes config bootstrap
```

### 8. Reinstall the modules and sync them

Composer keeps a copy of every module `composer.json` in `vendor/composer/installed.json`, and Laravel
discovers the providers there. Reinstall the modules, so that the deleted providers are forgotten; this also
rebuilds the package manifest, and Artisan works again. Use the vendor of your modules:

```bash
composer update "app/*"
php artisan module:sync
```

`module:sync` excludes the module vendor from Packagist and adds the `autoload-dev` of the module tests.
Existing requirements (`"app/blog": "*"` or `"^1.0"`) are kept.

### 9. Replace the generators

The `module:make:*` commands are replaced by the Laravel generators with `--module`:

| 2.x                                            | 3.0                                                          |
|------------------------------------------------|--------------------------------------------------------------|
| `module:make:model Post blog`                  | `make:model Post --module=blog`                              |
| `module:make:controller PostController blog`   | `make:controller PostController --module=blog`               |
| `module:make:migration create_posts_table blog` | `make:migration create_posts_table --module=blog`           |
| `module:make:test PostTest blog`               | `make:test PostTest --module=blog`                           |
| `module:make:action CreatePost blog`           | `make:action CreatePost --module=blog` (lorisleiva/laravel-actions) |
| `module:make:dto PostData blog`                | `make:data PostData --module=blog` (spatie/laravel-data)     |
| the other `module:make:<type>`                 | `make:<type> ... --module=<module>`                          |
| `module:make:route`, `module:make:query-wizard` | removed: create the files by hand or with your own generator |
| `module:stub:publish`, `custom_stubs`          | `php artisan stub:publish`, which customizes the Laravel stubs |

`make:data` adds the `Data` suffix to names that do not end with it: `make:data CreatePostDTO` creates
`CreatePostDTOData`. Pass the full name without a suffix, or configure the suffix of laravel-data.

The generated code follows the Laravel stubs, not the Porto stubs of 2.x. If you relied on the 2.x stubs,
put your versions in `stubs/` with `php artisan stub:publish`; they apply to the application and to the
modules alike.

### 10. Replace the migration commands

Module migrations are registered with the Laravel migrator:

| 2.x                              | 3.0                                                          |
|----------------------------------|--------------------------------------------------------------|
| `module:migrate`                 | `migrate`                                                    |
| `module:migrate blog`            | `migrate --path=modules/blog/database/migrations`            |
| `module:migrate:rollback`, `:refresh`, `:reset`, `:status` | `migrate:rollback`, `migrate:refresh`, `migrate:reset`, `migrate:status` |

Update scripts, CI jobs and deployment that call the old commands. `module:delete` now deletes one module
at a time, takes the directory name (`blog`, not `app/blog`), and needs `--force` in non-interactive mode.

### 11. Replace the module templates

`module:make --preset=plain|base|api --entity=...` is replaced by module templates: `module:make blog
--preset=<preset>` renders `stubs/module/<preset>`. Start from the built-in template, which is published to
`stubs/module/default`, and add the files your modules begin with:

```bash
php artisan vendor:publish --tag=modules-stubs
cp -R stubs/module/default stubs/module/api
```

The placeholders are described in the [README](README.md#module-templates). `--entity` is gone: use the
module name with filters, for example `{{ name|studly }}`.

### 12. Clean up

- Models can drop their `newFactory()` method when the factory is in `database/factories` with the same
  name: the package resolves it in the console. Keep `newFactory()` (or add `#[UseFactory]`) for models
  that create factories while serving HTTP requests.
- Remove the deployment steps that cleared `bootstrap/cache` to refresh the module cache. Run
  `php artisan optimize` when you deploy; it caches the modules together with the config and routes.
  2.x wrote the cache by itself in production, 3.0 does not: without `optimize`, every process scans the modules.
- `octane.watch` does not need the modules path anymore; it is added automatically.

### Check the upgrade

```bash
php artisan optimize:clear
php artisan module:doctor
php artisan route:list --json \
  | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;' \
  > /tmp/routes-after.json
diff /tmp/routes-before.json /tmp/routes-after.json
php artisan test
php artisan optimize && php artisan optimize:clear
```

- `module:doctor` reports no errors.
- The routes are the same, except for the prefixes of nested route directories (step 4).
- The tests pass.
