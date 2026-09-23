<?php

declare(strict_types=1);

arch('source files are strict')
    ->expect('Laraneat\Modules')
    ->toUseStrictTypes();

arch('classes are final')
    ->expect('Laraneat\Modules')
    ->classes()
    ->toBeFinal();

arch('exceptions are marked')
    ->expect('Laraneat\Modules\Exceptions')
    ->classes()
    ->toImplement('Laraneat\Modules\Exceptions\ModulesException');

foreach (['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit', 'env', 'getenv', 'putenv'] as $function) {
    arch("{$function}() is not used")
        ->expect($function)
        ->not->toBeUsedIn('Laraneat\Modules');
}

foreach (['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'Symfony\Component\Process\Process'] as $process) {
    arch("{$process} is not used: processes run through the Process facade")
        ->expect($process)
        ->not->toBeUsedIn('Laraneat\Modules');
}

arch('Composer runs only in the composer runner')
    ->expect('Illuminate\Support\Facades\Process')
    ->toOnlyBeUsedIn('Laraneat\Modules\Scaffold\ComposerRunner');

arch('the package does not depend on Composer internals')
    ->expect('Laraneat\Modules')
    ->not->toUse('Composer');

$runtime = [
    'Laraneat\Modules\Module',
    'Laraneat\Modules\ModuleRepository',
    'Laraneat\Modules\Facades',
    'Laraneat\Modules\Manifest',
    'Laraneat\Modules\Registrars',
];

foreach ($runtime as $namespace) {
    foreach ([
        'Laraneat\Modules\Commands',
        'Laraneat\Modules\Generators',
        'Laraneat\Modules\Scaffold\ApplicationComposer',
        'Laraneat\Modules\Scaffold\ComposerJson',
        'Laraneat\Modules\Scaffold\ComposerRunner',
        'Laraneat\Modules\Scaffold\InstalledPackages',
        'Laraneat\Modules\Scaffold\TemplateRenderer',
    ] as $console) {
        arch("{$namespace} does not use the console-only {$console}")
            ->expect($namespace)
            ->not->toUse($console);
    }
}
