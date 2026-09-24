# Changelog

All notable changes to this project are documented in this file.

## 3.0.0 - Unreleased

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
- An invalid module `composer.json` fails the application boot, and two modules can not share a namespace.
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
