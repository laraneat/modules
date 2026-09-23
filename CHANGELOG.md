# Changelog

All notable changes to this project are documented in this file.

## 3.0.0 - Unreleased

Version 3 is a rewrite. See [UPGRADE.md](UPGRADE.md) for the upgrade from 2.x.

### Added

- `--module` for every generator: the Laravel `make:*` commands, `make:migration`, the table migration generators
  and the generators of other packages that extend `GeneratorCommand` (`make:action`, `make:data`, ...).
- Module resources are loaded by convention: config, translations, views, Blade components, migrations,
  factories, seeders, routes and commands. Modules no longer need their own service provider.
- Route groups in `config/modules.php`; nested route directories are appended to the group prefix.
- The `generators` config maps generator commands to namespaces inside the modules.
- `Modules::seeders()` returns the seeders of all modules, in the order of their `_N` suffix.
- Module templates in `stubs/module/<preset>` with `{{ variable|filter }}` placeholders in paths and contents.
- `module:doctor` checks the modules and how they are installed.
- `module:make`, `module:sync` and `module:delete` run Composer themselves; `--no-update` prints the command instead.
- Module vendors are excluded from Packagist, so a missing module is never replaced by a public package.
- The module manifest is cached by `php artisan optimize` and cleared by `optimize:clear`; `MODULES_CACHE`
  changes the cache path.
- The modules path is added to `octane.watch`.
- The `about` command shows the modules.

### Changed

- Requires PHP 8.3 and Laravel 13.12.
- The facade is `Laraneat\Modules\Facades\Modules`; modules are identified by their directory name.
- `Laraneat\Modules\Module` is a read-only value object with public properties.
- The views and translations namespace of a module is its directory name.
- `module:delete` deletes one module, uninstalls it with Composer before deleting its files, and never deletes
  a symlinked module directory.
- Without the cache file, the manifest is built once per process, so it is never stale in development.

### Removed

- The `module:make:*` component generators and their stubs, `module:stub:publish` and the `custom_stubs` config.
- `module:migrate` and the `module:migrate:*` commands: use `migrate` and the `migrate:*` commands.
- `Laraneat\Modules\Support\ModuleServiceProvider`, `CanLoadRoutesFromDirectory` and `CanRunModuleSeeders`.
- `InteractsWithTestUser` and `WithJsonResponseHelpers`: UPGRADE.md has versions to copy into the application.
- The `components`, `composer.author`, `user_model`, `create_permission` and `cache` config keys.
- The `composer/composer` dependency.
