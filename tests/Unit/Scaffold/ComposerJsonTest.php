<?php

declare(strict_types=1);

use Laraneat\Modules\Exceptions\ComposerFailed;
use Laraneat\Modules\Scaffold\ComposerJson;

const PATH_REPOSITORY = ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]];

const PACKAGIST_EXCLUDING_APP = ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*']];

/**
 * @param  array<string, mixed>|string  $contents
 * @return array<string, mixed>
 */
function editComposerJson(string $directory, array|string $contents, Closure $edit): array
{
    $path = $directory.'/composer.json';
    file_put_contents($path, is_string($contents) ? $contents : json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $composerJson = ComposerJson::read($path);
    $edit($composerJson);
    $composerJson->save();

    return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function addBlog(ComposerJson $composerJson): void
{
    $composerJson->addModule('app/blog', 'modules/*', ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']);
}

it('adds a module: path repository, requirement, dev autoload and the Packagist filter', function () {
    $json = editComposerJson($this->directory, ['name' => 'demo/app', 'require' => ['php' => '^8.3']], addBlog(...));

    expect($json)->toBe([
        'name' => 'demo/app',
        'require' => ['php' => '^8.3', 'app/blog' => '*@dev'],
        'repositories' => [PATH_REPOSITORY, PACKAGIST_EXCLUDING_APP],
        'autoload-dev' => ['psr-4' => ['Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    ]);
});

it('is idempotent', function () {
    $once = editComposerJson($this->directory, ['name' => 'demo/app'], addBlog(...));
    $twice = editComposerJson($this->directory, $once, addBlog(...));

    expect($twice)->toBe($once);
});

it('adds more modules to the same repository and filter', function () {
    $json = editComposerJson($this->directory, ['name' => 'demo/app'], function (ComposerJson $composerJson): void {
        addBlog($composerJson);
        $composerJson->addModule('app/shop', 'modules/*', []);
        $composerJson->addModule('acme/billing', 'modules/*', []);
    });

    expect($json['require'])->toBe(['app/blog' => '*@dev', 'app/shop' => '*@dev', 'acme/billing' => '*@dev'])
        ->and($json['repositories'])->toBe([
            PATH_REPOSITORY,
            ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/*', 'acme/*']],
        ]);
});

it('keeps an existing requirement of the module', function () {
    $json = editComposerJson($this->directory, ['require' => ['app/blog' => '^1.0']], addBlog(...));

    expect($json['require'])->toBe(['app/blog' => '^1.0']);
});

it('sorts the requirements when composer sorts packages', function () {
    $json = editComposerJson($this->directory, [
        'require' => ['php' => '^8.3', 'laravel/framework' => '^13.0', 'ext-json' => '*'],
        'config' => ['sort-packages' => true],
    ], addBlog(...));

    expect(array_keys($json['require']))->toBe(['php', 'ext-json', 'app/blog', 'laravel/framework']);
});

it('sorts the requirements like Composer', function () {
    $json = editComposerJson($this->directory, [
        'require' => array_fill_keys(['zeta/pkg', 'composer-runtime-api', 'lib-pcre', 'ext-mbstring', 'hhvm', 'php-64bit', 'ext-json', 'php', 'ext-10', 'app/wiki'], '*'),
        'config' => ['sort-packages' => true],
    ], addBlog(...));

    expect(array_keys($json['require']))->toBe([
        'php', 'php-64bit', 'hhvm', 'ext-10', 'ext-json', 'ext-mbstring', 'lib-pcre', 'composer-runtime-api', 'app/blog', 'app/wiki', 'zeta/pkg',
    ]);
});

it('adds a new requirement last when composer does not sort packages', function () {
    $json = editComposerJson($this->directory, ['require' => ['php' => '^8.3', 'laravel/framework' => '^13.0']], addBlog(...));

    expect(array_keys($json['require']))->toBe(['php', 'laravel/framework', 'app/blog']);
});

it('reuses an existing path repository', function () {
    $json = editComposerJson($this->directory, [
        'repositories' => [['type' => 'path', 'url' => 'modules/*']],
    ], addBlog(...));

    expect($json['repositories'])->toBe([['type' => 'path', 'url' => 'modules/*'], PACKAGIST_EXCLUDING_APP]);
});

it('adds the path repository when others point elsewhere', function () {
    $json = editComposerJson($this->directory, [
        'repositories' => [['type' => 'path', 'url' => 'packages/*'], ['type' => 'vcs', 'url' => 'modules/*'], ['packagist.org' => false]],
    ], addBlog(...));

    expect($json['repositories'])->toBe([
        ['type' => 'path', 'url' => 'packages/*'],
        ['type' => 'vcs', 'url' => 'modules/*'],
        ['packagist.org' => false],
        PATH_REPOSITORY,
    ]);
});

it('extends an existing packagist repository', function (string $url) {
    $json = editComposerJson($this->directory, [
        'repositories' => [['type' => 'composer', 'url' => $url, 'exclude' => ['other/*']], ['packagist.org' => false]],
    ], addBlog(...));

    expect($json['repositories'])->toBe([
        ['type' => 'composer', 'url' => $url, 'exclude' => ['other/*', 'app/*']],
        ['packagist.org' => false],
        PATH_REPOSITORY,
    ]);
})->with(['https://repo.packagist.org', 'https://repo.packagist.org/', 'https://packagist.org', 'http://repo.packagist.org/packages']);

it('adds the filter to a packagist repository without one', function () {
    $json = editComposerJson($this->directory, ['repositories' => [['type' => 'composer', 'url' => 'https://repo.packagist.org']]], addBlog(...));

    expect($json['repositories'])->toBe([PACKAGIST_EXCLUDING_APP, PATH_REPOSITORY]);
});

it('does not take a mirror for packagist', function () {
    $mirror = ['type' => 'composer', 'url' => 'https://packagist.org.mirror.example.com'];

    $json = editComposerJson($this->directory, ['repositories' => [$mirror]], addBlog(...));

    expect($json['repositories'])->toBe([$mirror, PATH_REPOSITORY, PACKAGIST_EXCLUDING_APP]);
});

it('filters a mirror that replaces packagist by its name', function (string $name) {
    $mirror = ['type' => 'composer', 'url' => 'https://mirror.example.com'];

    $json = editComposerJson($this->directory, ['repositories' => [$name => $mirror, 'private' => ['type' => 'vcs', 'url' => 'x']]], addBlog(...));

    expect($json['repositories'])->toBe([
        $name => [...$mirror, 'exclude' => ['app/*']],
        'private' => ['type' => 'vcs', 'url' => 'x'],
        'modules' => PATH_REPOSITORY,
    ]);
})->with(['packagist.org', 'packagist']);

it('filters a mirror named packagist in the repository list and the default repository', function (string $name) {
    // "composer config repo.packagist composer <url>" writes this; Composer still uses the default repository.
    $mirror = ['name' => $name, 'type' => 'composer', 'url' => 'https://mirror.example.com'];

    $json = editComposerJson($this->directory, ['repositories' => [$mirror]], addBlog(...));

    expect($json['repositories'])->toBe([[...$mirror, 'exclude' => ['app/*']], PATH_REPOSITORY, PACKAGIST_EXCLUDING_APP]);
})->with(['packagist.org', 'packagist']);

it('leaves a packagist repository restricted with "only" untouched', function () {
    $repository = ['type' => 'composer', 'url' => 'https://repo.packagist.org', 'only' => ['laravel/*']];

    $json = editComposerJson($this->directory, ['repositories' => [$repository, ['packagist.org' => false]]], addBlog(...));

    expect($json['repositories'])->toBe([$repository, ['packagist.org' => false], PATH_REPOSITORY]);
});

it('fails when packagist serves the module through "only"', function () {
    $contents = ['repositories' => [['type' => 'composer', 'url' => 'https://repo.packagist.org', 'only' => ['laravel/*', 'app/*']]]];

    expect(fn () => editComposerJson($this->directory, $contents, addBlog(...)))
        ->toThrow(ComposerFailed::class, 'a Packagist repository serves [app/blog] through "only"');
});

it('does not enable packagist when it is disabled', function (array $disabled) {
    $json = editComposerJson($this->directory, ['repositories' => [$disabled]], addBlog(...));

    expect($json['repositories'])->toBe([$disabled, PATH_REPOSITORY]);
})->with([
    [['packagist.org' => false]],
    [['packagist' => false]],
]);

it('supports repositories defined as an object', function () {
    $json = editComposerJson($this->directory, [
        'repositories' => ['private' => ['type' => 'composer', 'url' => 'https://repo.example.com'], 'modules' => ['type' => 'vcs', 'url' => 'x']],
    ], addBlog(...));

    expect($json['repositories'])->toBe([
        'private' => ['type' => 'composer', 'url' => 'https://repo.example.com'],
        'modules' => ['type' => 'vcs', 'url' => 'x'],
        'modules-2' => PATH_REPOSITORY,
        'packagist.org' => PACKAGIST_EXCLUDING_APP,
    ]);
});

it('does not enable packagist when it is disabled in repositories defined as an object', function () {
    $json = editComposerJson($this->directory, ['repositories' => ['packagist.org' => false]], addBlog(...));

    expect($json['repositories'])->toBe(['packagist.org' => false, 'modules' => PATH_REPOSITORY]);
});

it('ignores repository entries that are not objects', function () {
    $json = editComposerJson($this->directory, ['repositories' => ['modules/*', ['type' => 'path']]], addBlog(...));

    expect($json['repositories'])->toBe(['modules/*', ['type' => 'path'], PATH_REPOSITORY, PACKAGIST_EXCLUDING_APP]);
});

it('keeps the formatting: empty objects, indentation, unicode and slashes', function () {
    $original = "{\n  \"name\": \"demo/app\",\n  \"description\": \"Приложение / app\",\n  \"require\": {},\n  \"extra\": {},\n  \"scripts\": {\n    \"test\": [\"pest\"]\n  }\n}\n";

    editComposerJson($this->directory, $original, addBlog(...));

    expect(file_get_contents($this->directory.'/composer.json'))->toBe(<<<'JSON'
    {
      "name": "demo/app",
      "description": "Приложение / app",
      "require": {
        "app/blog": "*@dev"
      },
      "extra": {},
      "scripts": {
        "test": [
          "pest"
        ]
      },
      "repositories": [
        {
          "type": "path",
          "url": "modules/*",
          "options": {
            "symlink": true
          }
        },
        {
          "type": "composer",
          "url": "https://repo.packagist.org",
          "exclude": [
            "app/*"
          ]
        }
      ],
      "autoload-dev": {
        "psr-4": {
          "Modules\\Blog\\Tests\\": "modules/blog/tests/"
        }
      }
    }

    JSON);
});

it('keeps tab indentation', function () {
    editComposerJson($this->directory, "{\n\t\"name\": \"demo/app\",\n\t\"extra\": {\n\t\t\"a\": 1\n\t}\n}", fn (ComposerJson $json) => $json->addProvider('X'));

    expect(file_get_contents($this->directory.'/composer.json'))
        ->toBe("{\n\t\"name\": \"demo/app\",\n\t\"extra\": {\n\t\t\"a\": 1,\n\t\t\"laravel\": {\n\t\t\t\"providers\": [\n\t\t\t\t\"X\"\n\t\t\t]\n\t\t}\n\t}\n}\n");
});

it('does not write the file when nothing changed', function () {
    $original = '{"name":"demo/app","require":{"app/blog":"*"},"repositories":[{"type":"path","url":"modules/*"},{"packagist.org":false}]}';
    file_put_contents($this->directory.'/composer.json', $original);

    $composerJson = ComposerJson::read($this->directory.'/composer.json');
    $composerJson->addModule('app/blog', 'modules/*', []);

    expect($composerJson->isDirty())->toBeFalse();

    $composerJson->save();

    expect(file_get_contents($this->directory.'/composer.json'))->toBe($original);
});

it('keeps the file mode', function () {
    file_put_contents($path = $this->directory.'/composer.json', '{}');
    chmod($path, 0640);

    ComposerJson::read($path)->addProvider('A')->save();
    clearstatcache();

    expect(fileperms($path) & 0777)->toBe(0640);
})->skipOnWindows();

it('replaces the file atomically', function () {
    editComposerJson($this->directory, ['name' => 'demo/app'], addBlog(...));

    expect(scandir($this->directory))->toBe(['.', '..', 'composer.json']);
});

it('saves again after more changes', function () {
    file_put_contents($this->directory.'/composer.json', '{}');
    $composerJson = ComposerJson::read($this->directory.'/composer.json');

    $composerJson->addProvider('A')->save();
    expect($composerJson->isDirty())->toBeFalse();

    $composerJson->addProvider('B')->save();

    expect(json_decode((string) file_get_contents($this->directory.'/composer.json'), true))
        ->toBe(['extra' => ['laravel' => ['providers' => ['A', 'B']]]]);
});

it('removes a requirement and dev autoload namespaces', function () {
    $json = editComposerJson($this->directory, [
        'require' => ['php' => '^8.3', 'app/blog' => '*'],
        'autoload-dev' => ['psr-4' => ['Tests\\' => 'tests/', 'Modules\\Blog\\Tests\\' => 'modules/blog/tests/']],
    ], function (ComposerJson $composerJson): void {
        $composerJson->removeRequire('app/blog')->removeRequire('app/missing');
        $composerJson->removeAutoloadDev(['Modules\\Blog\\Tests\\', 'Missing\\']);
    });

    expect($json)->toBe([
        'require' => ['php' => '^8.3'],
        'autoload-dev' => ['psr-4' => ['Tests\\' => 'tests/']],
    ]);
});

it('keeps a module required for development', function () {
    $json = editComposerJson($this->directory, ['require-dev' => ['app/blog' => '*@dev']], addBlog(...));

    expect($json)->not->toHaveKey('require')
        ->and($json['require-dev'])->toBe(['app/blog' => '*@dev']);

    $json = editComposerJson($this->directory, $json, fn (ComposerJson $composerJson) => $composerJson->removeRequire('app/blog'));

    expect($json['require-dev'])->toBe([]);
});

it('removes nothing when there is nothing to remove', function () {
    $json = editComposerJson($this->directory, ['name' => 'demo/app'], function (ComposerJson $composerJson): void {
        $composerJson->removeRequire('app/blog')->removeAutoloadDev(['Modules\\Blog\\Tests\\']);
    });

    expect($json)->toBe(['name' => 'demo/app']);
});

it('adds a provider once', function () {
    $json = editComposerJson($this->directory, [
        'extra' => ['laravel' => ['providers' => ['Modules\\Blog\\Providers\\EventServiceProvider'], 'aliases' => []]],
    ], function (ComposerJson $composerJson): void {
        $composerJson->addProvider('Modules\\Blog\\Providers\\BlogServiceProvider');
        $composerJson->addProvider('Modules\\Blog\\Providers\\BlogServiceProvider');
    });

    expect($json['extra']['laravel'])->toBe([
        'providers' => ['Modules\\Blog\\Providers\\EventServiceProvider', 'Modules\\Blog\\Providers\\BlogServiceProvider'],
        'aliases' => [],
    ]);
});

it('reads values', function () {
    file_put_contents($this->directory.'/composer.json', '{"name":"demo/app","require":{"app/blog":"*","vendor/pkg.name":"^1"},"autoload-dev":{"psr-4":{"A\\\\":"a/"}}}');

    $composerJson = ComposerJson::read($this->directory.'/composer.json');

    expect($composerJson->get('name'))->toBe('demo/app')
        ->and($composerJson->get('autoload-dev.psr-4'))->toBe(['A\\' => 'a/'])
        ->and($composerJson->get('missing'))->toBeNull()
        ->and($composerJson->requires('app/blog'))->toBeTrue()
        ->and($composerJson->requires('vendor/pkg.name'))->toBeTrue()
        ->and($composerJson->requires('app/shop'))->toBeFalse()
        ->and($composerJson->requires('php'))->toBeFalse()
        ->and($composerJson->toArray()['require'])->toBe(['app/blog' => '*', 'vendor/pkg.name' => '^1']);
});

it('tells whether packagist can serve a package', function (array $repositories, bool $excluded) {
    file_put_contents($this->directory.'/composer.json', json_encode(['repositories' => $repositories]));

    expect(ComposerJson::read($this->directory.'/composer.json')->isExcludedFromPackagist('app/blog'))->toBe($excluded);
})->with([
    'default packagist' => [[], false],
    'other repositories only' => [[['type' => 'path', 'url' => 'modules/*']], false],
    'excluded vendor' => [[PACKAGIST_EXCLUDING_APP, ['packagist.org' => false]], true],
    'excluded package' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['app/blog']], ['packagist.org' => false]], true],
    'excluded pattern with uppercase' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['APP/*']], ['packagist.org' => false]], true],
    'other vendor excluded' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['acme/*']], ['packagist.org' => false]], false],
    'similar vendor excluded' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'exclude' => ['ap/*']], ['packagist.org' => false]], false],
    'only other vendors' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'only' => ['laravel/*']], ['packagist.org' => false]], true],
    'only the vendor' => [[['type' => 'composer', 'url' => 'https://repo.packagist.org', 'only' => ['app/*']], ['packagist.org' => false]], false],
    'disabled' => [[['packagist.org' => false]], true],
    'disabled by name' => [['packagist.org' => false], true],
    'disabled, then redefined' => [[['packagist.org' => false], ['type' => 'composer', 'url' => 'https://repo.packagist.org']], false],
    'mirror excluding the vendor' => [['packagist.org' => ['type' => 'composer', 'url' => 'https://mirror.example.com', 'exclude' => ['app/*']]], true],
    'mirror' => [['packagist' => ['type' => 'composer', 'url' => 'https://mirror.example.com']], false],
    'unnamed mirror' => [[['type' => 'composer', 'url' => 'https://mirror.example.com', 'exclude' => ['app/*']]], false],
    'replaced by another kind of repository' => [['packagist.org' => ['type' => 'vcs', 'url' => 'https://example.com/repo.git']], true],
    'disabled by name, then redefined' => [['packagist.org' => false, 'main' => ['type' => 'composer', 'url' => 'https://repo.packagist.org']], false],
    'after entries that are not objects' => [['modules/*', PACKAGIST_EXCLUDING_APP], true],
    'named mirror' => [[['name' => 'packagist', 'type' => 'composer', 'url' => 'https://mirror.example.com'], PACKAGIST_EXCLUDING_APP], false],
    'named mirror excluding the vendor' => [[['name' => 'packagist.org', 'type' => 'composer', 'url' => 'https://mirror.example.com', 'exclude' => ['app/*']], PACKAGIST_EXCLUDING_APP], true],
    'named mirror next to the default repository' => [[['name' => 'packagist.org', 'type' => 'composer', 'url' => 'https://mirror.example.com', 'exclude' => ['app/*']]], false],
    'named mirror restricted to other vendors' => [[['name' => 'packagist', 'type' => 'composer', 'url' => 'https://mirror.example.com', 'only' => ['laravel/*']], ['packagist.org' => false]], true],
    'not a composer repository' => [[['type' => 'vcs', 'url' => 'https://packagist.org/app/blog', 'exclude' => ['app/*']]], false],
]);

it('fails on files it can not edit', function (?string $contents, string $message) {
    $path = $this->directory.'/composer.json';

    if ($contents !== null) {
        file_put_contents($path, $contents);
    }

    expect(fn () => ComposerJson::read($path)->addModule('app/blog', 'modules/*', []))->toThrow(ComposerFailed::class, $message);
})->with([
    'missing file' => [null, 'Unable to read'],
    'invalid JSON' => ['{', 'is not valid JSON'],
    'not an object' => ['[]', 'must contain a JSON object'],
    'require is a list' => ['{"require": ["app/blog"]}', '"require" must be an object'],
    'repositories is a string' => ['{"repositories": "modules/*"}', '"repositories" must be an array or an object'],
]);
