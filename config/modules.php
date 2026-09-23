<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | The directory that holds the modules. Every direct subdirectory with a
    | composer.json file is a module. Keep it inside the application, so the
    | module paths stay relative and "octane:start --watch" can see them.
    |
    */

    'path' => base_path('modules'),

    /*
    |--------------------------------------------------------------------------
    | Namespace And Vendor Of New Modules
    |--------------------------------------------------------------------------
    |
    | "module:make blog" creates the "Modules\Blog" namespace and the
    | "app/blog" Composer package. The vendor is excluded from Packagist,
    | so choose one that you do not publish public packages under.
    |
    */

    'namespace' => 'Modules',

    'vendor' => 'app',

    /*
    |--------------------------------------------------------------------------
    | Route Groups
    |--------------------------------------------------------------------------
    |
    | Every PHP file found in a module's "path" directory is loaded inside a
    | route group with the other attributes. Nested directories are added to
    | the prefix: "routes/api/v1/posts.php" is loaded with the "api/v1" prefix.
    |
    */

    'routes' => [
        'api' => ['path' => 'routes/api', 'prefix' => 'api', 'middleware' => ['api']],
        'web' => ['path' => 'routes/web', 'middleware' => ['web']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Namespaces
    |--------------------------------------------------------------------------
    |
    | "make:* --module=blog" places classes where Laravel places them in the
    | application ("Models", "Http\Controllers", ...). Map a command to
    | another sub-namespace of the module here. Module commands are
    | discovered in the namespace of "make:command".
    |
    | Example: 'make:controller' => 'UI\API\Controllers'
    |
    */

    'generators' => [
        //
    ],

];
