<?php

declare(strict_types=1);

it('lets Tinker alias the classes of the modules', function () {
    expect(config('tinker.alias'))->toBe(['Modules\\Blog\\', 'Modules\\ShopOrder\\']);
});

it('keeps the aliases of the application', function () {
    $this->files(['config/tinker.php' => '<?php return ["alias" => ["Vendor\\\\Package\\\\", "Modules\\\\Blog\\\\"]];']);
    $this->reboot();

    expect(config('tinker.alias'))->toBe(['Vendor\\Package\\', 'Modules\\Blog\\', 'Modules\\ShopOrder\\']);
});

it('does not add aliases without modules', function () {
    $this->files(['config/modules.php' => '<?php return ["path" => base_path("empty")];']);
    $this->reboot();

    expect(config('tinker.alias'))->toBeNull();
});
