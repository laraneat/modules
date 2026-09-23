<?php

declare(strict_types=1);

use Laraneat\Modules\Exceptions\InvalidTemplate;
use Laraneat\Modules\Scaffold\TemplateRenderer;

const TEMPLATE_VARIABLES = [
    'name' => 'article-category',
    'namespace' => 'Modules\\ArticleCategory',
    'package' => 'app/article-category',
    'vendor' => 'app',
    'date' => '2026_09_23_120000',
];

it('renders stubs, copies other files and keeps empty directories', function () {
    $template = $this->files([
        'composer.json.stub' => '{"name": "{{ package }}", "psr-4": "{{ namespace|json }}\\\\"}',
        'src/Models/.gitkeep' => '',
        'resources/views/{{ name }}.blade.php' => '{{ $title }} and {{ name }} stay as they are',
        'README.md.stub' => "# {{name}}\n",
    ]);

    expect((new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))->toBe([
        'README.md' => "# article-category\n",
        'composer.json' => '{"name": "app/article-category", "psr-4": "Modules\\\\ArticleCategory\\\\"}',
        'resources/views/article-category.blade.php' => '{{ $title }} and {{ name }} stay as they are',
        'src/Models/.gitkeep' => '',
    ]);
});

it('applies filters from left to right', function (string $placeholder, string $result) {
    $template = $this->files(['file.stub' => $placeholder]);

    expect((new TemplateRenderer)->render($template, TEMPLATE_VARIABLES)['file'])->toBe($result);
})->with([
    ['{{ name }}', 'article-category'],
    ['{{ name|studly }}', 'ArticleCategory'],
    ['{{ name|camel }}', 'articleCategory'],
    ['{{ name|snake }}', 'article_category'],
    ['{{ name|kebab }}', 'article-category'],
    ['{{ name|studly|kebab }}', 'article-category'],
    ['{{ name|plural }}', 'article-categories'],
    ['{{ name|plural|snake }}', 'article_categories'],
    ['{{ name|plural|studly }}', 'ArticleCategories'],
    ['{{ name|studly|singular }}', 'ArticleCategory'],
    ['{{ name|upper }}', 'ARTICLE-CATEGORY'],
    ['{{ name|studly|lower }}', 'articlecategory'],
    ['{{ name|title }}', 'Article-Category'],
    ['{{ namespace|json }}', 'Modules\\\\ArticleCategory'],
    ['{{ package|json }}', 'app/article-category'],
    ['{{  name  |  studly  }}', 'ArticleCategory'],
    ['{{name|studly}}', 'ArticleCategory'],
    ['{{ date }}_create_{{ name|plural|snake }}_table', '2026_09_23_120000_create_article_categories_table'],
    ['{{ vendor }}/{{ name }}', 'app/article-category'],
    ['{{ missing', '{{ missing'],
    ['{ name }', '{ name }'],
]);

it('renders placeholders in directory and file names', function () {
    $template = $this->files([
        'src/{{ name|studly }}/{{ name|studly }}Service.php.stub' => 'namespace {{ namespace }}\\{{ name|studly }};',
        'database/migrations/{{ date }}_create_{{ name|plural|snake }}_table.php' => '<?php',
    ]);

    expect((new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))->toBe([
        'database/migrations/2026_09_23_120000_create_article_categories_table.php' => '<?php',
        'src/ArticleCategory/ArticleCategoryService.php' => 'namespace Modules\\ArticleCategory\\ArticleCategory;',
    ]);
})->skip(DIRECTORY_SEPARATOR === '\\', 'Windows does not allow "|" in file names.');

it('fails on unknown variables and filters before anything is written', function (string $file, string $contents, string $reason) {
    if (DIRECTORY_SEPARATOR === '\\' && str_contains($file, '|')) {
        $this->markTestSkipped('Windows does not allow "|" in file names.');
    }

    $template = $this->files([$file => $contents]);

    expect(fn () => (new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))
        ->toThrow(InvalidTemplate::class, "Invalid module template [{$file}]: {$reason}");
})->with([
    'unknown variable' => ['a.stub', '{{ model }}', 'unknown variable [model].'],
    'unknown filter' => ['a.stub', '{{ name|reverse }}', 'unknown filter [reverse].'],
    'unknown variable in a path' => ['{{ model }}.php', '', 'unknown variable [model].'],
    'unknown filter in a path' => ['{{ name|slug }}.php', '', 'unknown filter [slug].'],
]);

it('does not render placeholders in the contents of files without .stub', function () {
    $template = $this->files(['view.blade.php' => '{{ model }}']);

    expect((new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))->toBe(['view.blade.php' => '{{ model }}']);
});

it('refuses paths that would leave the module directory', function (array $variables, string $file) {
    $template = $this->files([$file => '']);

    expect(fn () => (new TemplateRenderer)->render($template, [...TEMPLATE_VARIABLES, ...$variables]))
        ->toThrow(InvalidTemplate::class, 'the path renders to an unsafe name');
})->with([
    'parent directory' => [['name' => '..'], '{{ name }}/evil.php'],
    'current directory' => [['name' => '.'], '{{ name }}/file.php'],
    'empty segment' => [['name' => ''], '{{ name }}/file.php'],
    'slash' => [['name' => '../../etc'], '{{ name }}.php'],
    'backslash' => [[], '{{ namespace }}.php'],
    'drive letter' => [['name' => 'C:'], '{{ name }}/file.php'],
    'nul byte' => [['name' => "evil\0"], '{{ name }}.php'],
]);

it('refuses two files rendered to the same path', function () {
    $template = $this->files([
        'composer.json' => '{}',
        'composer.json.stub' => '{}',
    ]);

    expect(fn () => (new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))
        ->toThrow(InvalidTemplate::class, 'another file is also rendered to [composer.json].');
});

it('refuses a path rendered both as a file and as a directory', function () {
    $template = $this->files([
        'composer.json' => '{}',
        '{{ name }}.stub' => '',
        'article-category/src/file.php' => '',
    ]);

    expect(fn () => (new TemplateRenderer)->render($template, TEMPLATE_VARIABLES))
        ->toThrow(InvalidTemplate::class, 'Invalid module template [{{ name }}.stub]: the file is rendered to [article-category], which is also the directory of [article-category/src/file.php].');
});

it('accepts a template path with a trailing slash', function () {
    $template = $this->files(['README.md.stub' => '{{ name }}']);

    expect((new TemplateRenderer)->render($template.'/', TEMPLATE_VARIABLES))->toBe(['README.md' => 'article-category']);
});

it('fails when the template directory does not exist', function () {
    expect(fn () => (new TemplateRenderer)->render($this->directory.'/missing', TEMPLATE_VARIABLES))
        ->toThrow(InvalidTemplate::class, 'the directory does not exist.');
});

it('renders the built-in template', function () {
    $files = (new TemplateRenderer)->render(__DIR__.'/../../../resources/stubs/module/default', TEMPLATE_VARIABLES);

    expect(array_keys($files))->toBe([
        'composer.json',
        'database/factories/.gitkeep',
        'database/migrations/.gitkeep',
        'database/seeders/.gitkeep',
        'src/Models/.gitkeep',
        'tests/.gitkeep',
    ])->and(json_decode($files['composer.json'], true, 512, JSON_THROW_ON_ERROR))->toBe([
        'name' => 'app/article-category',
        'description' => 'The article-category module.',
        'type' => 'library',
        'autoload' => [
            'psr-4' => [
                'Modules\\ArticleCategory\\' => 'src/',
                'Modules\\ArticleCategory\\Database\\Factories\\' => 'database/factories/',
                'Modules\\ArticleCategory\\Database\\Seeders\\' => 'database/seeders/',
            ],
        ],
        'autoload-dev' => [
            'psr-4' => [
                'Modules\\ArticleCategory\\Tests\\' => 'tests/',
            ],
        ],
    ]);
});
