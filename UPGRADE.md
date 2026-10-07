# Upgrade Guide

## From 2.x to 3.0

Version 3 is a smaller package: it loads modules by convention and adds `--module` to the Laravel generators.
The component generators, stubs, migration commands and test helpers of 2.x are gone.

The steps below are written so that a developer or an AI agent can follow them one by one. Commit after
every step, and run the checks at the end.

### Before you start

- The application runs Laravel 13.12 or newer and PHP 8.3 or newer, with `laraneat/modules` 2.1 or newer: the
  first 2.x release that supports Laravel 13. The steps compare 3.0 with it.
- The test suite passes.
- Every directory of `modules/` with a `composer.json` is a valid module: the file is valid JSON with a valid
  package `name`, its first `autoload.psr-4` entry maps the root namespace of the module
  (`"Modules\\Blog\\": "src/"`), and no two modules share a package name or a namespace. 2.x skipped a module
  without a name; in 3.0 an invalid module is an `InvalidModule` exception while the application boots, for
  HTTP requests too. The message names the module and the reason.
- Save the routes of the application to compare them later, sorted by URI and in the order they are
  registered:

  ```bash
  for sort in uri definition; do
    php artisan route:list --json --sort=$sort \
      | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;' \
      > /tmp/routes-before-$sort.json
  done
  ```

  The first file shows the routes that appear, disappear or change. Only the second one shows a change of
  the order, which decides the route that answers when two of them match the same URL.

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
| `user_model`                     | removed, see [step 6](#6-copy-the-test-and-response-helpers-into-the-application) |
| `create_permission`              | removed: only the 2.x generators used it                                     |
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
    'make:test' => 'UI\API',
],
```

`make:test` creates tests in `tests/Feature` and `tests/Unit` unless it is mapped: the line above keeps the
`tests/UI/API` directory of 2.x. Create the tests of `tests/UI/CLI` and `tests/UI/WEB` with a qualified name:
`make:test "Modules\Blog\Tests\UI\CLI\PublishPostsTest" --module=blog`.

Laravel puts models in `Models` only when the module has a `src/Models` directory, and in the root namespace
otherwise. Add `'make:model' => 'Models'` to always use `Models`, as 2.x did.

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
   patterns, model bindings, rate limiters) in another provider of the module: keep the module service
   provider for it, or create one and add it to `extra.laravel.providers`. Define route patterns and macros
   in its `register()` method (see step 4). Keep the route service provider of a module whose routes do
   not fit the route groups, and except the module from them (see step 4).
4. Remove the calls that load the module directories themselves: `mergeConfigFrom()`, `loadMigrationsFrom()`,
   `loadViewsFrom()` and `loadTranslationsFrom()` with paths of the module. One exception: keep
   `mergeConfigFrom()` in a provider that reads the module config in its `register()` method (see below).

This step is about the providers of the modules. Keep the routing of the application itself (`withRouting()`
in `bootstrap/app.php`, or `App\Providers\RouteServiceProvider`) as it is.

The publish tags of the 2.x module providers (`<module>-config`, `-migrations`, `-translations`, `-views`)
are gone with them: update the scripts that call `vendor:publish` with these tags.

What 3.0 loads, and how it differs from the 2.x providers:

- **Config**: every `config/*.php` file of the module, keyed by its file name. In 2.x only
  `config/<module>.php` was merged, and only when `loadConfigurations()` was called. Check that the other
  files of `config/` are meant to be config. The application config still wins; module config files are
  not publishable anymore.
- **Config in `register()`**: the package merges the module config while its own provider registers. The
  providers of modules whose vendor sorts before `laraneat` (the default `app` does) register earlier, so
  `config('blog.enabled')` in their `register()` is `null`. It is `null` only without the config cache: the
  code works in production and fails in development and in the tests. In 2.x the provider merged its config
  itself. Find the reads with `grep -rn "config(" modules/*/src/Providers`, and move them to `boot()` or
  into the closures of the bindings, or keep `$this->mergeConfigFrom()` for that file at the top of
  `register()`.
- **Views and translations**: the namespace is the module directory name (`blog::`). In 2.x it was the kebab
  case name of the module; rename the directory or the references if they differ. Views published into
  `resources/views/modules/<module>` or `resources/views/vendor/<module>` do not override the module views
  anymore: move the changes into the module. Translation overrides in `lang/vendor/<module>` still work.
- **Migrations and commands**: registered only in the console. 2.x registered migrations for HTTP requests
  too: `Artisan::call('migrate')` during an HTTP request does not see the module migrations anymore.
- **Everything is loaded**: the 2.x provider stub had `loadCommands()`, `loadTranslations()` and `loadViews()`
  commented out. 3.0 loads the commands, translations and views of every module that has them.

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

Use the prefix, middleware and other attributes of the deleted route service providers.

A group loads the `path` directory of **every** module. A module whose routes do not fit the groups (another
prefix, its own middleware, no `web` middleware) keeps its route service provider and loads them with
`Route::group()`; replace `loadRoutesFromDirectory()` there, the trait is removed. Then leave the module out
of the group. Otherwise its files are registered a second time, with the prefix and the middleware of the
group and without the middleware of the module, an `auth` middleware for example:

```php
'api' => ['path' => 'src/UI/API/routes', 'prefix' => 'api', 'middleware' => ['api'], 'except' => ['billing', 'import']],
```

`except` takes module directory names. `module:doctor` reports a name in `except` that is not a module.

Differences from `CanLoadRoutesFromDirectory`:

- Nested directories are appended to the prefix. In 2.x, `routes/v1/admin/stats.php` got the `api/admin`
  prefix; now it gets `api/v1/admin`. Routes one directory deep keep their URIs.
- The files of a directory are loaded before its subdirectories (2.x loaded subdirectories first), and
  groups are loaded for all modules in turn: first every `api` route, then every `web` route.
- Route files are loaded while the package provider boots. 2.x loaded them right after the module route
  service provider booted, so `Route::pattern()` calls and route macros in its `boot()` applied to its routes.
  When you move them to another provider, define them in its `register()` method, or use `->where()` in the
  route files: the `boot()` of another provider may run after the module routes are loaded.
- The modules are loaded in the order of their directory names. In 2.x the order was that of the module
  route service providers: package discovery sorts them by package name (`app/blog`). The two differ when
  the modules have several vendors, or a package name that is not the directory name.
- The routes of the modules are registered together, at the place of `laraneat/modules` among the
  discovered packages. A package whose name sorts between two modules registered its routes between
  theirs; now they are before or after the routes of all modules. The routes of a module in `except` are
  registered by its own provider, at the place of the module package: for the default `app` vendor, before
  the routes of the groups.
- `$this` in a route file is not the route service provider of the module anymore: it is the
  `Illuminate\Routing\RouteFileRegistrar` of Laravel, as in `routes/web.php`. Use the `Route` facade.

When two routes can match the same URL (`posts/{post}` and `posts/export`), make sure the specific one is
still registered first: compare the `definition` files in [Check the upgrade](#check-the-upgrade).

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
  `grep -rn "TestUser([^)]*\[\])" modules tests`. The match inside the copied trait is expected.

### 7. Update the code that uses the package API

- The facade is `Laraneat\Modules\Facades\Modules` (was `Laraneat\Modules\Support\Facades\Modules`). The
  global `Modules` alias points to the new facade.
- `Laraneat\Modules\ModulesRepository` is `Laraneat\Modules\ModuleRepository`. The classes of
  `Laraneat\Modules\Providers` are replaced by one `Laraneat\Modules\ModulesServiceProvider`.
- The 2.x exceptions are removed, except `ModuleNotFound`: its `make()`, `makeForName()` and
  `makeForNameOrPackageName()` are replaced by `ModuleNotFound::named()`. Every exception of 3.0 implements the
  `Laraneat\Modules\Exceptions\ModulesException` interface.
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
| `(string) $module`                         | `$module->package`                             |
| `Module::macro()`                          | removed: `Module` is a read-only object         |

```bash
grep -rnE 'Laraneat\\Modules\\(Support|Enums|Exceptions|Providers|Commands|ModulesRepository|Module;)|Modules::' \
  app modules tests database routes config bootstrap
```

The grep also finds the calls of the new facade `Laraneat\Modules\Facades\Modules`, like the
`Modules::seeders()` of step 5: they are already correct.

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
`CreatePostDTOData`. Pass `--suffix=` to keep the name as given, or set `data.commands.make.suffix` (for
example to `DTO`) in the laravel-data config.

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

`migrate` also runs the migrations of the application, in one order by file name. In production it asks for
confirmation, like `module:migrate` did: keep `--force` in deployment scripts.

Update scripts, CI jobs and deployment that call the old commands. `module:delete` now deletes one module
at a time, takes the directory name (`blog`, not `app/blog`), and needs `--force` in non-interactive mode.
`module:make` has no `--force` option anymore and never overwrites a module: delete the module first.

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
- Run `php artisan optimize` when you deploy; it caches the modules together with the config and routes.
  2.x wrote the cache by itself in production, 3.0 does not: without `optimize`, every process scans the modules.
- The deployment steps that cleared `bootstrap/cache` to refresh the module cache are not needed: one
  `optimize` is enough. Run it before `migrate`, and see "Performance and caching" in the README for
  `opcache.enable_cli=1`.
- Stop the processes of the old release before the first `optimize` of 3.0 when they share
  `bootstrap/cache`: `packages.php` and `services.php` then list the provider of 3.0, which the old code
  does not have.
- Remove the code of the application that added the module namespaces to `tinker.alias`: the package does it.
- `octane.watch` does not need the modules path anymore; it is added automatically.

### Check the upgrade

```bash
php artisan optimize:clear
php artisan module:doctor
for sort in uri definition; do
  php artisan route:list --json --sort=$sort \
    | php -r 'echo json_encode(json_decode(stream_get_contents(STDIN)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;' \
    > /tmp/routes-after-$sort.json
  diff /tmp/routes-before-$sort.json /tmp/routes-after-$sort.json
done
php artisan test
php artisan optimize && php artisan optimize:clear
```

- `module:doctor` reports no errors.
- The `uri` files are the same, except for the prefixes of nested route directories (step 4). A route that
  appears twice, or with the middleware of a route group in place of its own, belongs to a module that
  loads its routes itself: add the module to `except` (step 4).
- The `definition` files differ only in the order: the groups are loaded one after another (every `api`
  route, then every `web` route). Check every pair of routes that can match the same URL and changed places.
- The tests pass.
