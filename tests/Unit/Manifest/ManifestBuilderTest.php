<?php

declare(strict_types=1);

use Laraneat\Modules\Exceptions\InvalidModule;
use Laraneat\Modules\Manifest\ManifestBuilder;

const FIXTURE_MODULES = __DIR__.'/../../Fixtures/app/modules';

function manifestBuilder(string $modulesPath, array $routes = ['api' => 'routes/api', 'web' => 'routes/web'], string $commands = 'Console\\Commands'): ManifestBuilder
{
    return new ManifestBuilder($modulesPath, $routes, $commands);
}

function phpClass(string $class, string $declaration = 'class'): string
{
    return "<?php\n\n{$declaration} {$class}\n{\n}\n";
}

function moduleComposerJson(string $name, array $psr4 = ['Modules\\Demo\\' => 'src/'], array $extra = []): string
{
    return json_encode(['name' => $name, 'autoload' => ['psr-4' => $psr4], ...$extra], JSON_THROW_ON_ERROR);
}

it('describes every module of the fixture application', function () {
    $modules = str_replace('\\', '/', (string) realpath(FIXTURE_MODULES));

    expect(manifestBuilder($modules)->build())->toBe([
        'blog' => [
            'package' => 'app/blog',
            'namespace' => 'Modules\\Blog',
            'path' => $modules.'/blog',
            'src' => 'src',
            'config' => ['blog'],
            'lang' => true,
            'json' => true,
            'views' => true,
            'migrations' => true,
            'routes' => [
                'api' => [
                    '' => ['routes/api/posts.php'],
                    'v1' => ['routes/api/v1/feed.php'],
                    'v1/admin' => ['routes/api/v1/admin/stats.php'],
                ],
                'web' => ['' => ['routes/web/pages.php']],
            ],
            'seeders' => [
                '' => [
                    'Modules\\Blog\\Database\\Seeders\\BlogSeeder',
                    'Modules\\Blog\\Database\\Seeders\\CategorySeeder_10',
                    'Modules\\Blog\\Database\\Seeders\\PostSeeder_2',
                    'Modules\\Blog\\Database\\Seeders\\SeederHelper_3',
                ],
                'Deployment' => [
                    'Modules\\Blog\\Database\\Seeders\\Deployment\\BaseDeploymentSeeder_0',
                    'Modules\\Blog\\Database\\Seeders\\Deployment\\DemoSeeder_1',
                ],
            ],
            'commands' => [
                'Modules\\Blog\\Console\\Commands\\BlogCommand',
                'Modules\\Blog\\Console\\Commands\\PublishPostsCommand',
                'Modules\\Blog\\Console\\Commands\\Nested\\ArchivePostsCommand',
            ],
        ],
        'shop-order' => [
            'package' => 'app/shop-order',
            'namespace' => 'Modules\\ShopOrder',
            'path' => $modules.'/shop-order',
            'src' => 'src',
            'config' => ['shop-order'],
            'lang' => true,
            'json' => false,
            'views' => false,
            'migrations' => false,
            'routes' => [
                'web' => ['' => ['routes/web/orders.php']],
            ],
            'seeders' => [
                '' => ['Modules\\ShopOrder\\Database\\Seeders\\OrderSeeder_1'],
                'Deployment' => ['Modules\\ShopOrder\\Database\\Seeders\\Deployment\\ShipmentSeeder_1'],
            ],
            'commands' => [],
        ],
    ]);
});

it('finds nothing when the modules directory does not exist', function () {
    expect(manifestBuilder($this->directory.'/missing')->build())->toBe([]);
});

it('skips directories without composer.json, files and hidden directories', function () {
    $modules = $this->files([
        'notes/README.md' => 'Not a module',
        'README.md' => 'Not a module',
        '.hidden/composer.json' => moduleComposerJson('app/hidden'),
        'demo/composer.json' => moduleComposerJson('app/demo'),
    ]);

    expect(array_keys(manifestBuilder($modules)->build()))->toBe(['demo']);
});

it('orders modules by directory name', function () {
    $modules = $this->files([
        'zeta/composer.json' => moduleComposerJson('app/zeta', ['Modules\\Zeta\\' => 'src/']),
        'alpha/composer.json' => moduleComposerJson('app/alpha', ['Modules\\Alpha\\' => 'src/']),
        'mid-dle/composer.json' => moduleComposerJson('app/mid-dle', ['Modules\\MidDle\\' => 'src/']),
    ]);

    expect(array_keys(manifestBuilder($modules)->build()))->toBe(['alpha', 'mid-dle', 'zeta']);
});

it('uses the first psr-4 entry as the root namespace and source directory', function (array $psr4, string $namespace, string $src) {
    $modules = $this->files(['demo/composer.json' => moduleComposerJson('app/demo', $psr4)]);

    $module = manifestBuilder($modules)->build()['demo'];

    expect($module['namespace'])->toBe($namespace)
        ->and($module['src'])->toBe($src);
})->with([
    'src directory' => [['Modules\\Demo\\' => 'src/'], 'Modules\\Demo', 'src'],
    'without trailing slash' => [['Modules\\Demo\\' => 'src'], 'Modules\\Demo', 'src'],
    'with ./ prefix' => [['Modules\\Demo\\' => './app'], 'Modules\\Demo', 'app'],
    'module root' => [['Modules\\Demo\\' => ''], 'Modules\\Demo', ''],
    'list of directories' => [['App\\Demo\\' => ['src/', 'lib/']], 'App\\Demo', 'src'],
    'nested directory' => [['Acme\\Shop\\Demo\\' => 'code/php/'], 'Acme\\Shop\\Demo', 'code/php'],
    'first entry wins' => [['Modules\\Demo\\' => 'src/', 'Modules\\Demo\\Database\\Factories\\' => 'database/factories/'], 'Modules\\Demo', 'src'],
]);

it('rejects invalid modules', function (string $composerJson, string $reason) {
    $modules = $this->files(['broken/composer.json' => $composerJson]);

    expect(fn () => manifestBuilder($modules)->build())
        ->toThrow(InvalidModule::class, "Invalid module [{$modules}/broken]: {$reason}");
})->with([
    'invalid JSON' => ['{"name": ', 'composer.json is not valid JSON'],
    'not an object' => ['"app/broken"', 'composer.json must contain an object.'],
    'no name' => ['{"autoload": {"psr-4": {"Modules\\\\Broken\\\\": "src/"}}}', 'composer.json must have a valid "name".'],
    'invalid name' => [moduleComposerJson('App/Broken'), 'composer.json must have a valid "name".'],
    'no autoload' => ['{"name": "app/broken"}', 'the first "autoload.psr-4" entry'],
    'autoload is not an object' => ['{"name": "app/broken", "autoload": "src"}', 'the first "autoload.psr-4" entry'],
    'empty psr-4' => ['{"name": "app/broken", "autoload": {"psr-4": {}}}', 'the first "autoload.psr-4" entry'],
    'no trailing backslash' => [moduleComposerJson('app/broken', ['Modules\\Broken' => 'src/']), 'the first "autoload.psr-4" entry'],
    'invalid namespace' => [moduleComposerJson('app/broken', ['Modules\\1Broken\\' => 'src/']), 'the first "autoload.psr-4" entry'],
    'invalid directory' => [moduleComposerJson('app/broken', ['Modules\\Broken\\' => 42]), 'the first "autoload.psr-4" entry'],
    'empty directory list' => [moduleComposerJson('app/broken', ['Modules\\Broken\\' => []]), 'the first "autoload.psr-4" entry'],
]);

it('rejects two modules with the same package name', function () {
    $modules = $this->files([
        'blog/composer.json' => moduleComposerJson('app/blog', ['Modules\\Blog\\' => 'src/']),
        'blog-copy/composer.json' => moduleComposerJson('app/blog', ['Modules\\BlogCopy\\' => 'src/']),
    ]);

    expect(fn () => manifestBuilder($modules)->build())
        ->toThrow(InvalidModule::class, 'the package name [app/blog] is already used by the [blog] module.');
});

it('rejects two modules with the same namespace', function () {
    $modules = $this->files([
        'blog/composer.json' => moduleComposerJson('app/blog', ['Modules\\Blog\\' => 'src/']),
        'blog-copy/composer.json' => moduleComposerJson('app/blog-copy', ['Modules\\Blog\\' => 'src/']),
    ]);

    expect(fn () => manifestBuilder($modules)->build())
        ->toThrow(InvalidModule::class, 'the namespace [Modules\\Blog] is already used by the [blog] module.');
});

it('finds config files by their key, top level php files only', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo'),
        'demo/config/demo.php' => '<?php return [];',
        'demo/config/demo-extra.php' => '<?php return [];',
        'demo/config/nested/ignored.php' => '<?php return [];',
        'demo/config/readme.md' => '',
    ]);

    expect(manifestBuilder($modules)->build()['demo']['config'])->toBe(['demo-extra', 'demo']);
});

it('records json translations only when the lang directory has json files', function (array $files, bool $lang, bool $json) {
    $modules = $this->files(['demo/composer.json' => moduleComposerJson('app/demo'), ...$files]);

    $module = manifestBuilder($modules)->build()['demo'];

    expect($module['lang'])->toBe($lang)->and($module['json'])->toBe($json);
})->with([
    'no lang directory' => [[], false, false],
    'php translations' => [['demo/lang/en/messages.php' => '<?php return [];'], true, false],
    'json translations' => [['demo/lang/de.json' => '{}'], true, true],
    'json in a subdirectory only' => [['demo/lang/en/extra.json' => '{}'], true, false],
]);

it('records views and migrations by their directories', function () {
    $modules = $this->files([
        'with/composer.json' => moduleComposerJson('app/with', ['Modules\\With\\' => 'src/']),
        'with/resources/views/.gitkeep' => '',
        'with/database/migrations/.gitkeep' => '',
        'without/composer.json' => moduleComposerJson('app/without', ['Modules\\Without\\' => 'src/']),
        'without/resources/.gitkeep' => '',
    ]);

    $manifest = manifestBuilder($modules)->build();

    expect($manifest['with']['views'])->toBeTrue()
        ->and($manifest['with']['migrations'])->toBeTrue()
        ->and($manifest['without']['views'])->toBeFalse()
        ->and($manifest['without']['migrations'])->toBeFalse();
});

it('collects route files per group, files before subdirectories, alphabetically', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo'),
        'demo/routes/api/b.php' => '<?php',
        'demo/routes/api/a.php' => '<?php',
        'demo/routes/api/notes.txt' => '',
        'demo/routes/api/.hidden.php' => '<?php',
        'demo/routes/api/a/z.php' => '<?php',
        'demo/routes/api/a/b/c.php' => '<?php',
        'demo/routes/api/empty/.gitkeep' => '',
        'demo/routes/api/v2/x.php' => '<?php',
        'demo/routes/web/page.php' => '<?php',
    ]);

    expect(manifestBuilder($modules)->build()['demo']['routes'])->toBe([
        'api' => [
            '' => ['routes/api/a.php', 'routes/api/b.php'],
            'a' => ['routes/api/a/z.php'],
            'a/b' => ['routes/api/a/b/c.php'],
            'v2' => ['routes/api/v2/x.php'],
        ],
        'web' => ['' => ['routes/web/page.php']],
    ]);
});

it('uses the route directories of the config', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo'),
        'demo/src/UI/API/routes/v1/posts.php' => '<?php',
        'demo/routes/api/ignored.php' => '<?php',
    ]);

    expect(manifestBuilder($modules, ['api' => '/src\\UI\\API/routes/'])->build()['demo']['routes'])
        ->toBe(['api' => ['v1' => ['src/UI/API/routes/v1/posts.php']]]);
});

it('collects seeders of database/seeders and its direct subdirectories only', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo'),
        'demo/database/seeders/RootSeeder.php' => phpClass('RootSeeder'),
        'demo/database/seeders/Deployment/DeploySeeder_1.php' => phpClass('DeploySeeder_1'),
        'demo/database/seeders/Deployment/Deeper/IgnoredSeeder.php' => phpClass('IgnoredSeeder'),
        'demo/database/seeders/not-a-namespace/IgnoredSeeder.php' => phpClass('IgnoredSeeder'),
        'demo/database/seeders/not-a-class.php' => '<?php',
        'demo/database/seeders/Empty/.gitkeep' => '',
    ]);

    expect(manifestBuilder($modules)->build()['demo']['seeders'])->toBe([
        '' => ['Modules\\Demo\\Database\\Seeders\\RootSeeder'],
        'Deployment' => ['Modules\\Demo\\Database\\Seeders\\Deployment\\DeploySeeder_1'],
    ]);
});

it('collects commands recursively from the make:command namespace', function (string $namespace, string $directory) {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo', ['Modules\\Demo\\' => 'app/']),
        "demo/app/{$directory}/SendCommand.php" => phpClass('SendCommand'),
        "demo/app/{$directory}/Deep/Er/CleanCommand.php" => phpClass('CleanCommand'),
        "demo/app/{$directory}/not-a-namespace/IgnoredCommand.php" => phpClass('IgnoredCommand'),
        'demo/app/Other/IgnoredCommand.php' => phpClass('IgnoredCommand'),
    ]);

    expect(manifestBuilder($modules, [], $namespace)->build()['demo']['commands'])->toBe([
        'Modules\\Demo\\'.trim($namespace, '\\').'\\SendCommand',
        'Modules\\Demo\\'.trim($namespace, '\\').'\\Deep\\Er\\CleanCommand',
    ]);
})->with([
    'laravel' => ['Console\\Commands', 'Console/Commands'],
    'porto' => ['\\UI\\CLI\\Commands\\', 'UI/CLI/Commands'],
]);

it('collects commands of a module whose root namespace is its directory', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo', ['Modules\\Demo\\' => '']),
        'demo/Console/Commands/SendCommand.php' => phpClass('SendCommand'),
    ]);

    expect(manifestBuilder($modules)->build()['demo']['commands'])->toBe(['Modules\\Demo\\Console\\Commands\\SendCommand']);
});

it('leaves out files that do not declare a class of their name', function () {
    $modules = $this->files([
        'demo/composer.json' => moduleComposerJson('app/demo'),
        'demo/src/Console/Commands/AbstractCommand.php' => phpClass('AbstractCommand', 'abstract class'),
        'demo/src/Console/Commands/AttributeCommand.php' => "<?php\n\n#[AsCommand('demo:attribute')] final class AttributeCommand {}\n",
        'demo/src/Console/Commands/CommandContract.php' => phpClass('CommandContract', 'interface'),
        'demo/src/Console/Commands/CommandTrait.php' => phpClass('CommandTrait', 'trait'),
        'demo/src/Console/Commands/IndentedCommand.php' => "<?php\n\nnamespace Demo {\n    final readonly class IndentedCommand {}\n}\n",
        'demo/src/Console/Commands/Status.php' => phpClass('Status', 'enum'),
        'demo/src/Console/Commands/helpers.php' => "<?php\n\nfunction helpers(): void {}\n",
        'demo/src/Console/Commands/Commented.php' => "<?php\n\n// class Commented\n/**\n * class Commented\n */\n",
        'demo/src/Console/Commands/Other.php' => phpClass('OtherCommand'),
        'demo/src/Console/Commands/Prefix.php' => phpClass('PrefixCommand'),
        'demo/database/seeders/helpers.php' => "<?php\n\nfunction seed(): void {}\n",
    ]);

    $module = manifestBuilder($modules)->build()['demo'];

    expect($module['commands'])->toBe([
        'Modules\\Demo\\Console\\Commands\\AbstractCommand',
        'Modules\\Demo\\Console\\Commands\\AttributeCommand',
        'Modules\\Demo\\Console\\Commands\\CommandContract',
        'Modules\\Demo\\Console\\Commands\\CommandTrait',
        'Modules\\Demo\\Console\\Commands\\IndentedCommand',
        'Modules\\Demo\\Console\\Commands\\Status',
    ])->and($module['seeders'])->toBe([]);
});

it('has a key that changes with the settings', function () {
    $key = manifestBuilder('/app/modules')->key();

    expect(manifestBuilder('/app/modules/')->key())->toBe($key)
        ->and(manifestBuilder('/app/other')->key())->not->toBe($key)
        ->and(manifestBuilder('/app/modules', ['api' => 'routes/api'])->key())->not->toBe($key)
        ->and(manifestBuilder('/app/modules', commands: 'UI\\CLI\\Commands')->key())->not->toBe($key);
});

it('normalizes the modules path', function () {
    expect(manifestBuilder('C:\\app\\modules\\')->modulesPath())->toBe('C:/app/modules');
});
