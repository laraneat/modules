# Laraneat Modules

Modular monolith infrastructure for Laravel. Every module is a Composer path package in `modules/`;
one service provider loads its config, routes, views, translations, migrations and commands by convention.
Any `make:*` command can generate into a module with `--module`.

```bash
php artisan module:make blog
php artisan make:model Post --module=blog -mfs
php artisan make:controller PostController --module=blog --api
```

## Why

In a Laravel application the code is grouped by type: one feature is spread over `app/Models`,
`app/Http/Controllers`, `database/migrations`, `routes/api.php` and `resources/views`, next to every other
feature. A modular monolith groups the code by feature instead and is still one application with one deploy.
Laravel has no built-in support for it: something has to autoload every module, load its routes, views,
translations, migrations and commands, and make the generators write into it. This package does that, and
stays out of the module code:

- **Modules are plain Composer packages.** A module needs only a `composer.json` with its autoload and
  dependencies. Its code does not use this package: no base classes, no `module.json`, no required service
  provider. It gets a provider only when it has bindings, event listeners or gates to register.
- **Laravel-native.** Modules use the Laravel generators, stubs and conventions (`Models`, `Http\Controllers`, ...)
  unless you map them elsewhere. `migrate`, `route:cache`, `optimize`, Tinker and Octane work as usual.
- **Fast.** The package scans the modules once and caches the result with `php artisan optimize`. A cached
  application does no filesystem scans, and HTTP requests never run the console-only parts.
- **Safe with Composer.** Modules are installed as symlinked path packages; their vendor is excluded from
  Packagist, so a missing module cannot be replaced by a public package with the same name.
  `module:doctor` checks the whole setup.

### Compared to other packages

As of September 2026:

|                           | Laraneat Modules                                              | [InterNACHI/modular](https://github.com/InterNACHI/modular) | [nWidart/laravel-modules](https://github.com/nWidart/laravel-modules) |
|---------------------------|---------------------------------------------------------------|--------------------------------------------|------------------------------------------------|
| Laravel                   | 13.12+                                                        | 11–13                                      | 13 (older majors: 5.4–12)                      |
| A module is               | a Composer path package                                       | a Composer path package                    | a directory with `module.json`; its `composer.json` is merged by `composer-merge-plugin` |
| Service provider per module | not needed                                                  | not needed                                 | generated; extends a provider class of the package and loads the routes, views and config of the module |
| Generators                | `--module` on every `GeneratorCommand`, of Laravel and of other packages | `--module` on a list of Laravel generators | own `module:make-*` commands and stubs         |
| Routes                    | route groups with a prefix and middleware from the config     | `routes/*.php` loaded as they are          | route service provider of the module           |
| Module config files       | merged                                                        | not loaded                                 | loaded by the module provider                  |
| Composer                  | `module:make`, `module:sync` and `module:delete` run it; Packagist exclusion | the application `composer.json` is edited, you run Composer | `composer-merge-plugin`                        |
| Event discovery           | no                                                            | yes                                        | yes, in the generated event provider           |
| Enable and disable modules | no                                                           | no                                         | yes                                            |

Choose nWidart/laravel-modules to enable and disable modules or to build module assets with their own Vite
config, and InterNACHI/modular on Laravel 11 or 12.

## Contents

- [Why](#why)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Modules](#modules)
- [Conventions](#conventions)
- [Routes](#routes)
- [Generators](#generators)
- [Module service providers](#module-service-providers)
- [Seeders](#seeders)
- [Tests](#tests)
- [Commands](#commands)
- [Configuration](#configuration)
- [Performance and caching](#performance-and-caching)
- [Octane](#octane)
- [Upgrading](#upgrading)

## Requirements

- PHP 8.3 or newer
- Laravel 13.12 or newer
- Composer 2

## Installation

```bash
composer require laraneat/modules
```

The service provider and the `Modules` facade are registered by package discovery. Publish the config
when you want to change the defaults:

```bash
php artisan vendor:publish --tag=modules-config
```

## Quick start

Create a module, a model with its migration, factory and seeder, a controller and a test:

```bash
php artisan module:make blog
php artisan make:model Post --module=blog -mfs
php artisan make:controller PostController --module=blog --api --model=Post
php artisan make:test PostTest --module=blog
```

Add a route file, then migrate:

```php
// modules/blog/routes/api/posts.php
use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::apiResource('posts', PostController::class); // /api/posts
```

```bash
php artisan migrate
```

The module now looks like this, and nothing in it depends on this package:

```text
modules/blog/
├── composer.json                  app/blog, namespace Modules\Blog
├── database/
│   ├── factories/PostFactory.php
│   ├── migrations/2026_09_24_120000_create_posts_table.php
│   └── seeders/PostSeeder.php
├── routes/api/posts.php           loaded with the "api" prefix and middleware
├── src/
│   ├── Http/Controllers/PostController.php
│   └── Models/Post.php
└── tests/Feature/PostTest.php
```

Next steps: add the module tests to `phpunit.xml` ([Tests](#tests)), call `Modules::seeders()` from the
`DatabaseSeeder` ([Seeders](#seeders)), and cache the modules on deploy with `php artisan optimize`.

## Modules

A module is a directory in `modules/` with a `composer.json`:

```json
{
    "name": "app/blog",
    "autoload": {
        "psr-4": {
            "Modules\\Blog\\": "src/",
            "Modules\\Blog\\Database\\Factories\\": "database/factories/",
            "Modules\\Blog\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Modules\\Blog\\Tests\\": "tests/"
        }
    }
}
```

- The directory name (`blog`) is the module name. It is also the namespace of the module views,
  translations and Blade components: `view('blog::index')`, `__('blog::messages.welcome')`, `<x-blog::alert />`.
- The first `autoload.psr-4` entry is the root namespace of the module.
- Every module must have a valid `composer.json` with a unique package name and namespace. An invalid
  module is an error while the application boots, for HTTP requests too. Artisan commands and the tests
  fail the same way, so it is caught before a deploy.

The application requires every module like any other package. You do not have to write this by hand:
`module:make` and `module:sync` maintain it.

```json
{
    "require": {
        "app/blog": "*@dev"
    },
    "repositories": [
        {"type": "path", "url": "modules/*", "options": {"symlink": true}},
        {"type": "composer", "url": "https://repo.packagist.org", "exclude": ["app/*"]}
    ],
    "autoload-dev": {
        "psr-4": {
            "Modules\\Blog\\Tests\\": "modules/blog/tests/"
        }
    }
}
```

- `*@dev` accepts the `dev-*` version of a path package with `"minimum-stability": "stable"`.
- The Packagist repository excludes the vendor of the modules, so Composer fails instead of installing
  a public package when a module directory is missing (another branch, a partial checkout). Composer uses
  this repository in place of the default one. Pick a vendor that you do not publish public packages under.
- Packagist mirrors defined in the project get the exclusion too: a repository with a `packagist.org` URL,
  a mirror under the `packagist` or `packagist.org` key of `"repositories": {...}`, and a list entry named
  `packagist` or `packagist.org`, which `composer config repo.packagist composer <url>` writes. Exclude the
  vendor by hand in any other repository that proxies Packagist.
- Mirrors of the global Composer config (`~/.composer/config.json`) are not edited, so define your mirror in
  the project `composer.json`. A global mirror under the `packagist.org` key is replaced by the Packagist
  entry of the project and is no longer used. A global mirror that Composer 2.10 wrote as a named list entry
  is still used, without the exclusion.

### Dependencies between modules

A module declares what it uses in its `composer.json`, other modules included:

```json
{
    "name": "app/blog",
    "require": {
        "app/users": "*"
    }
}
```

- Use `*`, not `*@dev`: Composer reads stability flags only from the application `composer.json`, which
  already requires every module with `*@dev`.
- After a change of `require`, run `php artisan module:sync`: it runs `composer update` for the module.
- `module:delete` fails while another module requires the module, and nothing is deleted. With `--no-update`
  Composer does not run, so this is not checked.

### Finding modules

The `Modules` facade finds modules by their directory name:

```php
use Laraneat\Modules\Facades\Modules;

Modules::all();         // array of Module, keyed by name
Modules::find('blog');  // Module or null
Modules::get('blog');   // Module, or throws ModuleNotFound
```

`Laraneat\Modules\Module` is a read-only object with the `name` (`shop-order`), `package` (`app/shop-order`),
`namespace` (`Modules\ShopOrder`), `path` and `sourcePath` (the absolute directory of the root namespace)
properties.

### Creating a module

```bash
php artisan module:make blog
php artisan module:make shop-order --preset=api
php artisan module:make blog --no-update
```

`module:make`:

1. renders the module template into `modules/blog`;
2. adds the module to the `composer.json` of the application: the path repository, the requirement,
   the Packagist exclusion, and the `autoload-dev` entries of the module tests;
3. runs `composer update app/blog`. With `--no-update` it only prints the command, which is useful in CI
   or when an agent drives the work.

The name is normalized to kebab case: `ShopOrder` becomes `shop-order` (namespace `Modules\ShopOrder`), `API`
becomes `api`. A name in kebab case is kept as it is.

### Module templates

The built-in template has a `composer.json` and empty convention directories. Your own templates live
in `stubs/module/<preset>`: `--preset=api` uses `stubs/module/api`, and `stubs/module/default` replaces the
built-in template. Publish the built-in template to `stubs/module/default`, and copy it to
`stubs/module/<preset>` to start a named preset:

```bash
php artisan vendor:publish --tag=modules-stubs
cp -R stubs/module/default stubs/module/api
```

- Files ending in `.stub` are rendered and lose the extension. Other files are copied as they are.
- Placeholders work in the contents of `.stub` files and in all paths: `{{ variable }}` or
  `{{ variable|filter|filter }}`.
- Variables:

  | Variable    | Example value                             |
  |-------------|-------------------------------------------|
  | `name`      | `article-category`                        |
  | `namespace` | `Modules\ArticleCategory`                 |
  | `package`   | `app/article-category`                    |
  | `vendor`    | `app`                                     |
  | `date`      | `2026_09_23_120000` (for migration names) |

- Filters: `studly`, `camel`, `snake`, `kebab`, `plural`, `singular`, `lower`, `upper`, `title`, and `json`
  (escapes backslashes for JSON strings). They apply from left to right: `{{ name|plural|snake }}` is
  `article_categories`.
- An unknown variable or filter is an error, and nothing is written.
- Rendered paths must stay inside the module directory.
- The template must have a `composer.json` (or `composer.json.stub`), and its package name must match the module.

```text
stubs/module/api/
├── composer.json.stub
├── routes/api/{{ name }}.php.stub
├── src/Models/{{ name|studly }}.php.stub
└── database/migrations/{{ date }}_create_{{ name|plural|snake }}_table.php.stub
```

## Conventions

The package loads everything it finds in a module. A missing directory is skipped.

| Module path                         | Loaded as                                                              |
|-------------------------------------|------------------------------------------------------------------------|
| `config/*.php`                      | config keyed by the file name: `config/blog.php` is `config('blog')`. The application config wins. |
| `lang/`                             | translations: `__('blog::messages.welcome')`, plus JSON files          |
| `resources/views/`                  | views: `view('blog::posts.index')`                                     |
| `src/View/Components/`              | Blade components: `<x-blog::alert />`, or the anonymous component `resources/views/components/alert.blade.php` |
| `database/migrations/`              | migrations of `php artisan migrate` (console only)                     |
| `database/factories/`               | factories of the module models (console only, see below)               |
| `database/seeders/`                 | seeders of `Modules::seeders()`                                        |
| `routes/api/`, `routes/web/`        | [route groups](#routes)                                                |
| `src/Console/Commands/`             | Artisan commands (console only)                                        |

`src/` stands for the directory of the root namespace (the first `autoload.psr-4` entry): `View/Components`
and `Console/Commands` are looked up there, or in the [namespaces](#namespaces) of `make:component` and
`make:command`.

A module config file can extend any key, `config/app.php` included. The merge is shallow: the top-level keys
of the application config replace those of the module.

### Factories

`Modules\Blog\Models\Post::factory()` uses `Modules\Blog\Database\Factories\PostFactory`, and the factory
knows its model, the way Laravel pairs `App\Models\Post` with `Database\Factories\PostFactory`. Classes outside
the modules keep the Laravel behavior.

The resolver is global (`Factory::guessFactoryNamesUsing()`): a resolver that the application sets in the
`boot()` method of its provider replaces it.

The factory resolver is registered only in the console (tests, seeders, Tinker), because factories are not
used while serving requests. If you create models with factories during HTTP requests, point the model at
its factory:

```php
use Illuminate\Database\Eloquent\Attributes\UseFactory;

#[UseFactory(PostFactory::class)]
final class Post extends Model
{
    use HasFactory;
}
```

### Module commands

Classes in the `make:command` namespace of a module (`src/Console/Commands`, including subdirectories) are
registered as Artisan commands. Abstract classes, classes that are not commands and files that do not declare
a class of their name are skipped. Commands
with the `#[AsCommand]` attribute are loaded lazily. Like migrations, module commands are registered only
in the console, so `Artisan::call()` cannot run them during an HTTP request.

### Tinker

Tinker aliases the module classes like the application classes: `Post::first()` works without the namespace.

### Policies and events

Laravel already finds `Modules\Blog\Policies\PostPolicy` for `Modules\Blog\Models\Post`. Events and
listeners are not discovered: register them in a [module service provider](#module-service-providers).

## Routes

Route groups are defined in `config/modules.php`:

```php
'routes' => [
    'api' => ['path' => 'routes/api', 'prefix' => 'api', 'middleware' => ['api']],
    'web' => ['path' => 'routes/web', 'middleware' => ['web']],
],
```

- Every `*.php` file in the `path` directory of a module is loaded inside `Route::group()` with the
  other attributes, so `prefix`, `middleware`, `as`, `domain`, `where` and the rest work as usual.
- Nested directories are appended to the prefix: `routes/api/v1/admin/stats.php` is loaded with the
  `api/v1/admin` prefix.
- Files of a directory are loaded before its subdirectories, in alphabetical order.
- Groups apply to all modules. A module with its own layout can load its routes in its service provider.
- Nothing is loaded when the routes are cached: `php artisan route:cache` includes the module routes.
- Route files are loaded while the package boots, like the routes of other packages: before the providers
  of the application boot. `Route::pattern()` and route macros from `AppServiceProvider::boot()` do not reach
  them. Define those in the `register()` method of a provider, or use `->where()` in the route files.

```php
// modules/blog/routes/api/posts.php
use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\PostController;

Route::apiResource('posts', PostController::class); // /api/posts
```

## Generators

Every generator accepts `--module`: all `make:*` commands of Laravel, `make:migration`, the table migrations
(`make:queue-table`, `make:session-table`, ...) and the generators of other packages that extend
`GeneratorCommand` (`make:action` of laravel-actions, `make:data` of laravel-data, ...).

```bash
php artisan make:model Post --module=blog -mfs --policy
php artisan make:request StorePostRequest --module=blog
php artisan make:migration create_comments_table --module=blog
php artisan make:test PostTest --module=blog
```

Classes go where Laravel puts them in the application, relative to the root namespace of the module.
Some generators are adjusted to the module:

| Command                      | In the `blog` module                                                        |
|------------------------------|-----------------------------------------------------------------------------|
| `make:model`                 | `src/Models` (if the directory exists, like in Laravel). With `-f`, the factory goes to `database/factories`. |
| `make:factory`, `make:seeder` | `database/factories`, `database/seeders` with the `Modules\Blog\Database\*` namespaces |
| `make:migration`             | `database/migrations`                                                       |
| `make:test`                  | `tests/Feature` or `tests/Unit` with the `Modules\Blog\Tests\*` namespaces  |
| `make:view`, `make:component`, `make:mail`, `make:notification` | views in `resources/views`, referenced as `blog::...` |
| `make:provider`              | `src/Providers`, registered in `extra.laravel.providers` of the module `composer.json` |
| `make:config`                | `config`                                                                    |

Nested commands run in the same module: `make:model Post --module=blog -mfs` creates the model, the
migration, the factory and the seeder in `blog`.

`--model` and `--parent` name models of the module: `--model=Post` is `Modules\Blog\Models\Post`, or a class of
the `make:model` namespace (see below). As in the application, they cannot reference a model of another namespace.

### Namespaces

To use another layout, map a command to a namespace of the module in `config/modules.php`:

```php
'generators' => [
    'make:controller' => 'UI\API\Controllers',
    'make:request' => 'UI\API\Requests',
    'make:command' => 'UI\CLI\Commands',
],
```

`make:controller PostController --module=blog` then creates `Modules\Blog\UI\API\Controllers\PostController`.
Nested commands use the config too: `make:model Post -a --module=blog` puts the controller and the form
requests there, and the controller imports the requests from `UI\API\Requests`.

- A namespace is relative to where the generator puts classes in a module: `Modules\Blog\Tests` for
  `make:test`, `Modules\Blog\Database\Factories` for `make:factory`, `Modules\Blog\Database\Seeders`
  for `make:seeder` and `Modules\Blog` for the others.
- A name that starts with the module namespace is used as it is: `make:action "Modules\Blog\Domain\Publish"`.
- An empty namespace is the root namespace of the module: `'make:policy' => ''` creates `Modules\Blog\PostPolicy`.
  The `make:command` namespace can not be empty, because module commands are discovered in it.
- The `make:model` namespace also applies to `--model` and `--parent` of the Laravel generators. The factory
  of `make:model -f` goes where the factory resolver looks for it, whatever the `make:factory` namespace.
- `Modules::seeders()` reads only direct subdirectories of `database/seeders`, so a `make:seeder` namespace
  has at most one segment.
- The `make:command` namespace is also where [module commands](#module-commands) are discovered, and the
  `make:component` namespace is where `<x-blog::...>` components are looked up.

The stubs of the generators are the Laravel ones: customize them with `php artisan stub:publish`.

## Module service providers

A module does not need a service provider. Add one for bindings, event listeners, gates or anything the
conventions do not cover:

```bash
php artisan make:provider BlogServiceProvider --module=blog
php artisan module:sync
```

The provider is registered in `extra.laravel.providers` of the module `composer.json`; `module:sync` installs
the changed module, so that Laravel package discovery picks it up.

Module providers are loaded by package discovery, in the order of the package names. The module config,
views and translations are registered while the Laraneat provider registers, which may happen after the
`register()` of a module provider. In `register()`, do not read config or use the `Modules` facade directly:
do it in `boot()` or inside the closures of your bindings.

## Seeders

`Modules::seeders()` returns the seeder classes of all modules, ready for `$this->call()`:

```php
use Laraneat\Modules\Facades\Modules;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Modules::seeders());
    }
}
```

- Without arguments it returns the seeders of `database/seeders`. Pass subdirectories to add them:
  `Modules::seeders('', 'Demo')` returns `database/seeders` and `database/seeders/Demo`,
  `Modules::seeders('Demo')` only `database/seeders/Demo`. Only direct subdirectories are read:
  `Modules::seeders('Demo/Extra')` returns nothing.
- Class names come from the file paths (PSR-4): a seeder whose namespace does not match its path is not found.
- Seeders run in the order of their integer `_N` class name suffix (`PermissionsSeeder_1` before
  `UsersSeeder_2`); seeders without a suffix run last. Equal suffixes run in the order of the class names.
- Abstract classes and classes that do not extend `Seeder` are skipped.

## Tests

Module tests live in `modules/<module>/tests`. `module:make` and `module:sync` add their namespace to the
`autoload-dev` of the application, because Composer does not autoload the `autoload-dev` of dependencies.

Add the module tests to `phpunit.xml`:

```xml
<testsuite name="Feature">
    <directory>tests/Feature</directory>
    <directory>modules/*/tests/Feature</directory>
</testsuite>
<testsuite name="Unit">
    <directory>tests/Unit</directory>
    <directory>modules/*/tests/Unit</directory>
</testsuite>
```

With Pest, also extend the test case in `tests/Pest.php`:

```php
pest()->extend(Tests\TestCase::class)->in('Feature', '../modules/*/tests/Feature');
```

Run the tests of one module by its path:

```bash
php artisan test modules/blog/tests
```

## Commands

| Command                         | Description |
|---------------------------------|-------------|
| `module:make <name>`            | Create a module. `--preset=<preset>` picks the template, `--no-update` skips `composer update`. |
| `module:sync`                   | Add every module to the `composer.json` of the application and run `composer update` for the modules that changed. `--no-update` only prints the command. |
| `module:delete <name>`          | Remove the `autoload-dev` entries of the module, run `composer remove` for it, then delete its directory. Asks for confirmation; `--force` skips it and is required in non-interactive mode. With `--no-update`, it edits `composer.json` instead of running Composer, deletes the directory and prints the `composer update` command to run. A symlinked module directory is never deleted. |
| `module:doctor`                 | Check the modules and how they are installed. Read-only; fails when it finds errors. |
| `module:list`                   | List the modules. With `-v`, also what is loaded from each of them. |
| `module:cache`                  | Cache the module manifest. Part of `php artisan optimize`. |
| `module:clear`                  | Remove the module manifest cache. Part of `php artisan optimize:clear`. |

Commands and `--module` take the module directory name (`blog`), not the package name (`app/blog`).
Composer runs in the application directory: `composer.phar` there if it exists, otherwise `composer` from `PATH`.

`module:doctor` checks:

- that every module is required, excluded from Packagist and installed, and that the installed package
  links to the module directory;
- that the installed package matches the module `composer.json` (providers, autoload);
- the `autoload-dev` entries of the module tests;
- that the root namespace directory exists and that factories and seeders are autoloaded;
- that the `extra.laravel.providers` classes exist;
- route files outside of the configured route groups;
- unknown keys in `config/modules.php`, an outdated manifest cache, and a modules path that
  `octane:start --watch` cannot watch.

Most problems are fixed by `php artisan module:sync`.

The `about` command shows the number of modules, their path and whether the manifest is cached.

## Configuration

```php
return [
    // The directory of the modules. Every direct subdirectory with a composer.json is a module.
    'path' => base_path('modules'),

    // The namespace prefix and the Composer vendor of new modules.
    'namespace' => 'Modules',
    'vendor' => 'app',

    // Route groups, see "Routes".
    'routes' => [
        'api' => ['path' => 'routes/api', 'prefix' => 'api', 'middleware' => ['api']],
        'web' => ['path' => 'routes/web', 'middleware' => ['web']],
    ],

    // Namespaces of the generators inside a module, see "Generators".
    'generators' => [],
];
```

## Performance and caching

The package builds a manifest of the modules: their packages, namespaces and the files it loads. The
provider loads everything from the manifest and does not look for module files by itself.

- `php artisan optimize` (or `module:cache`) writes the manifest to `bootstrap/cache/modules.php`, next to
  the config and route caches. A cached application loads it with one `require`. Set `MODULES_CACHE` to
  store it elsewhere, like `APP_CONFIG_CACHE`. It must be a real environment variable: `.env` is not read
  when the config is cached.
- Without the cache file, the manifest is built once per process, so it is never stale. The cache is written
  only by `optimize` and `module:cache`: a deploy that skips them scans the modules in every process.
- The cached manifest lists the files it loads. After `php artisan optimize` on a development machine, new
  modules, new `lang`, `resources/views` and `database/migrations` directories, and new route, config, seeder
  and command files are ignored until `php artisan optimize:clear` (or `module:clear`); deleted files are
  skipped.
- `module:make`, `module:sync` and `module:delete` rebuild the cache file if it exists.
- The config and route caches include the module config and routes, so the package skips them when they
  are cached.
- Migrations, commands, factories, Tinker aliases and the Octane watcher are registered only in the console.

Cache the application when you deploy:

```bash
php artisan optimize
```

## Octane

The package keeps no state that changes after boot, so it is safe for Octane workers.

`php artisan octane:start --watch` restarts the workers when module files change: the modules path is
added to `octane.watch` automatically, if the modules are inside the application.

## Upgrading

See [UPGRADE.md](UPGRADE.md) for the upgrade from 2.x, and [CHANGELOG.md](CHANGELOG.md) for the changes.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
