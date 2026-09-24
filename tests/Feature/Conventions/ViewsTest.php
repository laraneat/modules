<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('registers the views of the modules under their names', function () {
    expect(view('blog::index', ['title' => 'News'])->render())->toBe("Blog: News\n")
        ->and(app('view')->getFinder()->getHints())->toMatchArray(['blog' => [$this->path('modules/blog/resources/views')]])
        ->and(app('view')->getFinder()->getHints())->not->toHaveKey('shop-order');
});

it('does not look for view overrides in the vendor directory', function () {
    $this->files(['resources/views/vendor/blog/index.blade.php' => 'Overridden']);

    expect(view('blog::index', ['title' => 'News'])->render())->toBe("Blog: News\n");
});

it('registers the Blade components of the modules under their names', function () {
    $this->files([
        'modules/blog/src/View/Components/Badge.php' => <<<'PHP'
            <?php

            namespace Modules\Blog\View\Components;

            use Illuminate\View\Component;

            class Badge extends Component
            {
                public function render(): string
                {
                    return 'class badge';
                }
            }
            PHP,
        'modules/blog/resources/views/components/card.blade.php' => 'anonymous card',
    ]);

    expect(Blade::render('<x-blog::badge /> <x-blog::card />'))->toBe('class badge anonymous card');
});

it('registers the Blade components in the module root with an empty make:component namespace', function () {
    $this->files([
        'config/modules.php' => '<?php return ["generators" => ["make:component" => ""]];',
        'modules/blog/src/Badge.php' => <<<'PHP'
            <?php

            namespace Modules\Blog;

            use Illuminate\View\Component;

            class Badge extends Component
            {
                public function render(): string
                {
                    return 'root badge';
                }
            }
            PHP,
    ]);
    $this->reboot();

    expect(Blade::render('<x-blog::badge />'))->toBe('root badge');
});

it('registers the Blade components in the namespace of make:component', function () {
    $this->files([
        'config/modules.php' => '<?php return ["generators" => ["make:component" => "\\\\UI\\\\Components\\\\"]];',
        'modules/blog/src/UI/Components/Badge.php' => <<<'PHP'
            <?php

            namespace Modules\Blog\UI\Components;

            use Illuminate\View\Component;

            class Badge extends Component
            {
                public function render(): string
                {
                    return 'mapped badge';
                }
            }
            PHP,
    ]);
    $this->reboot();

    expect(Blade::render('<x-blog::badge />'))->toBe('mapped badge');
});
