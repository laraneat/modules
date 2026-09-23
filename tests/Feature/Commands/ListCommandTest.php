<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function listModules(array $options = []): string
{
    Artisan::call('module:list', $options);

    return preg_replace('/ +$/m', '', str_replace("\r\n", "\n", Artisan::output()));
}

it('lists the modules', function () {
    expect(listModules())->toBe(<<<'TXT'
        +------------+----------------+-------------------+--------------------+
        | Name       | Package        | Namespace         | Path               |
        +------------+----------------+-------------------+--------------------+
        | blog       | app/blog       | Modules\Blog      | modules/blog       |
        | shop-order | app/shop-order | Modules\ShopOrder | modules/shop-order |
        +------------+----------------+-------------------+--------------------+

        TXT);
});

it('lists what is loaded from the modules with -v', function () {
    $this->files(['modules/wiki/composer.json' => '{"name": "app/wiki", "autoload": {"psr-4": {"Modules\\\\Wiki\\\\": "src/"}}}']);
    $this->reboot();

    expect(listModules(['-v' => true]))->toBe(<<<'TXT'
        +------------+----------------+-------------------+--------------------+-----------------------------------------------------------------------------------------------------------+
        | Name       | Package        | Namespace         | Path               | Loads                                                                                                     |
        +------------+----------------+-------------------+--------------------+-----------------------------------------------------------------------------------------------------------+
        | blog       | app/blog       | Modules\Blog      | modules/blog       | config (blog), lang (+json), views, migrations, api routes (3), web routes (1), seeders (4), commands (2) |
        | shop-order | app/shop-order | Modules\ShopOrder | modules/shop-order | config (shop-order), lang, web routes (1), seeders (2)                                                    |
        | wiki       | app/wiki       | Modules\Wiki      | modules/wiki       | -                                                                                                         |
        +------------+----------------+-------------------+--------------------+-----------------------------------------------------------------------------------------------------------+

        TXT);
});

it('warns when there are no modules', function () {
    $this->files(['config/modules.php' => '<?php return ["path" => base_path("empty")];']);
    $this->reboot();

    expect(listModules())->toBe("\n   WARN  No modules found in [empty].\n\n");
});
