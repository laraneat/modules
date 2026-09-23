<?php

use Illuminate\Database\Seeder;
use Laraneat\Modules\Support\Concerns\CanRunModuleSeeders;

function writeSeeder(string $path, string $namespace, string $class): void
{
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, "<?php\n\nnamespace {$namespace};\n\nclass {$class} extends \\Illuminate\\Database\\Seeder\n{\n}\n");
}

function recordingModuleSeeder(): Seeder
{
    return new class () extends Seeder {
        use CanRunModuleSeeders;

        /** @var array<int, string> */
        public array $ranSeeders = [];

        public function call($class, $silent = false, array $parameters = [])
        {
            $this->ranSeeders[] = $class;

            return $this;
        }
    };
}

beforeEach(function () {
    $this->setModules([
        __DIR__ . '/../../fixtures/stubs/modules/valid/author',
        __DIR__ . '/../../fixtures/stubs/modules/valid/navigation',
    ], $this->app->basePath('/modules'));

    $author = $this->app->basePath('/modules/author/database/seeders');
    writeSeeder("$author/AuthorPermissionsSeeder_1.php", 'Modules\\Author\\Database\\Seeders', 'AuthorPermissionsSeeder_1');
    writeSeeder("$author/AuthorSeeder_10.php", 'Modules\\Author\\Database\\Seeders', 'AuthorSeeder_10');
    writeSeeder("$author/AuthorDataSeeder.php", 'Modules\\Author\\Database\\Seeders', 'AuthorDataSeeder');
    writeSeeder("$author/Deployment/AuthorDeploySeeder_2.php", 'Modules\\Author\\Database\\Seeders\\Deployment', 'AuthorDeploySeeder_2');
    writeSeeder("$author/Testing/AuthorTestingSeeder_3.php", 'Modules\\Author\\Database\\Seeders\\Testing', 'AuthorTestingSeeder_3');
    file_put_contents("$author/README.md", 'not a seeder');
    file_put_contents("$author/AuthorOldSeeder_1.php.orig", '<?php namespace Old; class AuthorOldSeeder_1 {}');

    $navigation = $this->app->basePath('/modules/navigation/database/seeders');
    writeSeeder("$navigation/NavigationPermissionsSeeder_1.php", 'Modules\\Navigation\\Database\\Seeders', 'NavigationPermissionsSeeder_1');
    writeSeeder("$navigation/NavigationSeeder_2.php", 'Modules\\Navigation\\Database\\Seeders', 'NavigationSeeder_2');
});

it('runs root seeders of all modules ordered by their integer suffix', function () {
    $seeder = recordingModuleSeeder();
    $seeder->runSeedersFromModules();

    expect($seeder->ranSeeders)->toBe([
        'Modules\\Author\\Database\\Seeders\\AuthorPermissionsSeeder_1',
        'Modules\\Navigation\\Database\\Seeders\\NavigationPermissionsSeeder_1',
        'Modules\\Navigation\\Database\\Seeders\\NavigationSeeder_2',
        'Modules\\Author\\Database\\Seeders\\AuthorSeeder_10',
        'Modules\\Author\\Database\\Seeders\\AuthorDataSeeder',
    ]);
});

it('includes every requested subdirectory, not only the first one', function (array $subdirectories) {
    $seeder = recordingModuleSeeder();
    $seeder->runSeedersFromModules($subdirectories);

    expect($seeder->ranSeeders)
        ->toContain('Modules\\Author\\Database\\Seeders\\Deployment\\AuthorDeploySeeder_2')
        ->toContain('Modules\\Author\\Database\\Seeders\\Testing\\AuthorTestingSeeder_3')
        ->toContain('Modules\\Author\\Database\\Seeders\\AuthorPermissionsSeeder_1')
        ->and($seeder->ranSeeders)->toHaveCount(7)
        ->and(array_unique($seeder->ranSeeders))->toHaveCount(7);
})->with([
    'without root' => [['Deployment', 'Testing']],
    'with root' => [['/', '/Deployment', '/Testing']],
]);

it('ignores files that are not php files', function () {
    $seeder = recordingModuleSeeder();
    $seeder->runSeedersFromModules();

    expect($seeder->ranSeeders)->not->toContain('Old\\AuthorOldSeeder_1');
});
