<?php

declare(strict_types=1);

it('adds the modules directory to the Octane watcher', function (array $watch, array $expected) {
    $this->files(['config/octane.php' => '<?php return ["watch" => '.var_export($watch, true).'];']);
    $this->reboot();

    expect(config('octane.watch'))->toBe($expected);
})->with([
    'appended' => [['app', 'routes'], ['app', 'routes', 'modules']],
    'already watched' => [['modules', 'app'], ['modules', 'app']],
    'empty' => [[], ['modules']],
]);

it('watches a nested modules directory by its relative path', function () {
    $this->files([
        'config/octane.php' => '<?php return ["watch" => ["app"]];',
        'config/modules.php' => '<?php return ["path" => base_path("src/modules")];',
    ]);
    $this->reboot();

    expect(config('octane.watch'))->toBe(['app', 'src/modules']);
});

it('does not configure the watcher when Octane is not configured', function () {
    expect(config('octane'))->toBeNull();
});

it('can not watch a modules directory outside of the application', function () {
    $this->files([
        'config/octane.php' => '<?php return ["watch" => ["app"]];',
        'config/modules.php' => '<?php return ["path" => "'.sys_get_temp_dir().'/elsewhere/modules"];',
    ]);
    $this->reboot();

    expect(config('octane.watch'))->toBe(['app']);
});

it('can not watch the application directory itself', function () {
    $this->files([
        'config/octane.php' => '<?php return ["watch" => ["app"]];',
        'config/modules.php' => '<?php return ["path" => base_path()];',
    ]);
    $this->reboot();

    expect(config('octane.watch'))->toBe(['app']);
});
