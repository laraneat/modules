# Changelog

All notable changes to this project are documented in this file.

## 3.1.0 - Unreleased

### Added

- The `except` key of a route group lists the modules that the group does not load: a module with its own
  prefix or middleware loads the files of the directory in its service provider and is no longer registered a
  second time by the group. `module:doctor` reports excepted modules that do not exist, and does not report
  the route files of an excepted module.

### Fixed

- `php artisan optimize` over an existing `bootstrap/cache/modules.php` cached the config and the routes
  from the manifest of the previous deploy: a new module, route file or module config file was missing until
  the second `optimize`. Only the first application of a process reads the manifest cache now: the
  application that `config:cache` and `route:cache` boot scans the modules.
- `module:cache` as a part of `optimize` used the route groups of the config cache that the process started
  from; it now reads them from a fresh application, as `route:cache` does.
- A process that had read the manifest cache read its compiled copy again after rewriting the file when
  `opcache.enable_cli` is on: the file is invalidated in OPcache when it is written.
- In an application without a route service provider (no `withRouting()`), module routes were not found by
  name (`route()`, `Route::has()`), and returned 404 when the routes were cached. The package now loads the
  cached routes and refreshes the name and action lookups there.

### Changed

- Only the first application of a process reads `bootstrap/cache/modules.php`; a later one builds the
  manifest from the modules on disk.
- UPGRADE.md: the route comparison also covers the order of the routes; the order of the modules, module
  config in `register()`, modules that load their routes themselves, invalid module `composer.json` files,
  `module:make --force` and the deploy steps are described.

## 3.0.1 - 2026-09-30

### Fixed

- Module commands and seeders whose class is declared on a line with other code (`namespace Blog; class
  Foo {}`, `/** ... */ final class Foo`) or with its name on the next line are discovered again.
- A broken symbolic link in a module commands or seeders directory no longer fails the application boot.
- `module:doctor` finds unloaded route files at any depth, and no longer reports hidden ones, which are never
  loaded.

## 3.0.0 - 2026-09-24

Version 3 is a rewrite. See [UPGRADE.md](UPGRADE.md) for the upgrade from 2.x.

### Added

- `--module` for every generator: the Laravel `make:*` commands, `make:migration`, the table migration generators
  and the generators of other packages that extend `GeneratorCommand` (`make:action`, `make:data`, ...).
- Module resources are loaded by convention: config, translations (JSON included), views, class and anonymous
  Blade components, migrations, factories, routes and commands. Modules no longer need their own service
  provider.
- Tinker aliases the module classes.
- Route groups in `config/modules.php`; nested route directories are appended to the group prefix.
- The `generators` config maps generator commands to namespaces inside the modules.
- `Modules::seeders()` returns the seeders of all modules, in the order of their `_N` suffix.
- Module templates in `stubs/module/<preset>` with `{{ variable|filter }}` placeholders in paths and contents.
- `module:doctor` checks the modules and how they are installed; `module:list -v` shows what each module loads.
- `Modules::all()`, `Modules::find()` and `Modules::get()` find modules by their directory name.
- The `modules-config` and `modules-stubs` publish tags.
- `--no-update` for `module:make`, `module:sync` and `module:delete`: edit `composer.json` and print the
  Composer command instead of running it.
- `module:sync` adds the `autoload-dev` of the module tests and updates only the modules that changed.
- Packagist mirrors defined in the project get the exclusion of the module vendor too: repositories with a
  `packagist.org` URL, repositories under the `packagist` or `packagist.org` key, and list entries named
  `packagist` or `packagist.org`.
- The module manifest is cached by `php artisan optimize` and cleared by `optimize:clear`; `MODULES_CACHE`
  changes the cache path.
- The modules path is added to `octane.watch`.
- The `about` command shows the modules.

### Changed

- Requires PHP 8.3 and Laravel 13.12.
- The facade is `Laraneat\Modules\Facades\Modules`; modules are identified by their directory name.
- The four providers of `Laraneat\Modules\Providers` are replaced by `Laraneat\Modules\ModulesServiceProvider`,
  and the config publish tag `config` by `modules-config`.
- `Laraneat\Modules\Module` is a read-only value object with public properties.
- The views and translations namespace of a module is its directory name. Every `config/*.php` file of a
  module is merged, not only `config/<module>.php`.
- Route files of a directory are loaded before its subdirectories.
- An invalid module `composer.json` fails the application boot, and two modules cannot share a namespace.
  2.x skipped a module without a name.
- `module:make` requires new modules with `*@dev` and has no `--force` option.
- `ModuleNotFound` is created with `ModuleNotFound::named()`; every exception implements `ModulesException`.
- `module:delete` deletes one module, found by its directory name, asks for confirmation and needs `--force`
  in non-interactive mode.
- The manifest cache is written only by `php artisan optimize` and `module:cache`, not automatically in
  production, to `bootstrap/cache/modules.php` instead of `bootstrap/cache/laraneat-modules.php`.
- `Laraneat\Modules\ModulesRepository` is renamed to `Laraneat\Modules\ModuleRepository`.
- The `composer.vendor` config key is renamed to `vendor`.
- Migrations are registered only in the console.

### Removed

- The `module:make:*` component generators and their stubs, `module:stub:publish` and the `custom_stubs` config.
- `module:migrate` and the `module:migrate:*` commands: use `migrate` and the `migrate:*` commands.
- `Laraneat\Modules\Support\ModuleServiceProvider`, `CanLoadRoutesFromDirectory` and `CanRunModuleSeeders`.
- The methods of `ModulesRepository` and the getters, macros and `toArray()` of `Module`: UPGRADE.md maps them
  to 3.0.
- `Laraneat\Modules\Enums`, `Laraneat\Modules\Support\Generator`, `Support\Composer`, `Support\ComposerJsonFile`,
  `Support\ModuleConfigWriter`, `Support\Migrations` and the 2.x exceptions except `ModuleNotFound`.
- `module:make --preset=plain|base|api` and `--entity`: module templates replace them.
- `InteractsWithTestUser` and `WithJsonResponseHelpers`: UPGRADE.md has versions to copy into the application.
- The `components`, `composer.author`, `user_model`, `create_permission` and `cache` config keys.
- The `composer/composer` dependency.

## 2.1.0 - 2026-09-23

The last 2.x feature release, and the starting point of the upgrade to 3.0.

### Added

- Laravel 13 and PHP 8.5 support.
- Adding a module to `composer.json` excludes the module vendor from Packagist, so a missing local module
  cannot be replaced by a public package with the same name.

### Fixed

- `module:delete` runs `composer remove` before deleting the module files, refuses to delete a symlinked
  module and fails when a module could not be deleted. `ModulesRepository::delete()` can throw
  `CannotDeleteModule`.
- Package names are validated with the Composer rules and passed to Composer after `--`; module package
  names that are not valid Composer names are rejected, and the configured author is JSON-escaped.
- Generator names are validated segment by segment, so `../` cannot write files outside of the module. A
  name with another invalid segment (`v1.1/foo`) is rejected too.
- `runSeedersFromModules()` includes every requested subdirectory: an array union dropped the first one, so
  its seeders now run.
- Seeders run in the order of their integer `_N` suffix (`_2` before `_10`), seeders without a suffix last,
  ties by class name.
- Route, seeder and provider loaders ignore files that are not `.php` files.
- `module:sync` fails when syncing fails.

## 2.0.0 - 2025-12-09

A rewrite of 1.x for PHP 8.1 and Laravel 10 to 12.

### Added

- `ModulesRepository` finds modules by their `composer.json` and caches the manifest in production.
- `module:sync` and `module:stub:publish`; the `CanLoadRoutesFromDirectory`, `CanRunModuleSeeders` and
  `InteractsWithTestUser` traits; a `ModuleServiceProvider` base class for module providers.

### Changed

- The package registers four providers of `Laraneat\Modules\Providers`: `ComposerServiceProvider`,
  `ConsoleServiceProvider`, `ModulesRepositoryServiceProvider` and `ModulesServiceProvider`.

### Removed

- Enabling and disabling modules (`EnableCommand`, `DisableCommand`, `FileActivator`).
- `InstallCommand`, `SetupCommand`, `UpdateCommand`, `UseCommand`, `UnUseCommand`, `SeedCommand`, `DumpCommand`
  and `ComponentsMakeCommand`.
- The global helper functions, the `FileRepository`, `Json` and `Migrator` classes and the traits of
  `src/Traits`.
