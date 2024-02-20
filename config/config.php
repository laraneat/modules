<?php

return [
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Modules Paths
        |--------------------------------------------------------------------------
        |
        | Here you define which folders will be scanned for modules.
        | Path glob patters are also supported.
        | Do not include the /vendor folder in this list, it is scanned automatically.
        |
        */
        'modules' => [
            base_path('app/Modules'),
        ],
    ],

    'generator' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path used for save the generated module.
        |
        */
        'path' => base_path('app/Modules'),

        /*
        |--------------------------------------------------------------------------
        | Default Module Namespace
        |--------------------------------------------------------------------------
        |
        | Default module namespace.
        |
        */
        'namespace' => 'App\\Modules',

        /*
        |--------------------------------------------------------------------------
        | Custom generator stubs path
        |--------------------------------------------------------------------------
        |
        | Place your custom stubs in this folder
        |
        */
        'custom_stubs' => base_path('stubs/modules'),

        /*
        |--------------------------------------------------------------------------
        | User model class
        |--------------------------------------------------------------------------
        |
        | Customize the User model of the application
        |
        */
        'user_model' => null,

        /*
        |--------------------------------------------------------------------------
        | "Create permission" classes
        |--------------------------------------------------------------------------
        |
        | Customize "create permission" classes
        |
        */
        'create_permission' => [
            'action' => null,
            'dto' => null
        ],

        /*
        |--------------------------------------------------------------------------
        | Component paths
        |--------------------------------------------------------------------------
        |
        | Customize the paths where the folders will be generated.
        | Set the generate key to `false` to not generate that folder when creating
        | a module
        |
        */
        'components' => [
            'action' => [
                'path' => 'Actions',
                'generate' => true
            ],
            'api-controller' => [
                'path' => 'UI/API/Controllers',
                'generate' => false
            ],
            'api-query-wizard' => [
                'path' => 'UI/API/QueryWizards',
                'generate' => true
            ],
            'api-request' => [
                'path' => 'UI/API/Requests',
                'generate' => true
            ],
            'api-resource' => [
                'path' => 'UI/API/Resources',
                'generate' => true
            ],
            'api-route' => [
                'path' => 'UI/API/Routes',
                'generate' => true
            ],
            'api-test' => [
                'path' => 'UI/API/Tests',
                'generate' => true
            ],
            'cli-command' => [
                'path' => 'UI/CLI/Commands',
                'generate' => false
            ],
            'cli-test' => [
                'path' => 'UI/CLI/Tests',
                'generate' => false
            ],
            'dto' => [
                'path' => 'DTO',
                'generate' => true
            ],
            'event' => [
                'path' => 'Events',
                'generate' => false
            ],
            'exception' => [
                'path' => 'Exceptions',
                'generate' => false
            ],
            'factory' => [
                'path' => 'Data/Factories',
                'generate' => true
            ],
            'feature-test' => [
                'path' => 'Tests/Feature',
                'generate' => false
            ],
            'job' => [
                'path' => 'Jobs',
                'generate' => false
            ],
            'lang' => [
                'path' => 'lang',
                'generate' => false
            ],
            'listener' => [
                'path' => 'Listeners',
                'generate' => false
            ],
            'mail' => [
                'path' => 'Mails',
                'generate' => false
            ],
            'middleware' => [
                'path' => 'Middleware',
                'generate' => false
            ],
            'migration' => [
                'path' => 'Data/Migrations',
                'generate' => true
            ],
            'model' => [
                'path' => 'Models',
                'generate' => true
            ],
            'notification' => [
                'path' => 'Notifications',
                'generate' => false
            ],
            'observer' => [
                'path' => 'Observers',
                'generate' => false
            ],
            'policy' => [
                'path' => 'Policies',
                'generate' => true
            ],
            'provider' => [
                'path' => 'Providers',
                'generate' => true
            ],
            'rule' => [
                'path' => 'Rules',
                'generate' => false
            ],
            'seeder' => [
                'path' => 'Data/Seeders',
                'generate' => true
            ],
            'web-controller' => [
                'path' => 'UI/WEB/Controllers',
                'generate' => false
            ],
            'web-request' => [
                'path' => 'UI/WEB/Requests',
                'generate' => false,
            ],
            'web-route' => [
                'path' => 'UI/WEB/Routes',
                'generate' => false
            ],
            'web-test' => [
                'path' => 'UI/WEB/Tests',
                'generate' => false
            ],
            'view' => [
                'path' => 'resources/views',
                'generate' => false
            ],
            'unit-test' => [
                'path' => 'Tests/Unit',
                'generate' => false
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for composer.json file, generated by this package
    |
    */
    'composer' => [
        'vendor' => 'app',
        'author' => [
            'name' => 'Example name',
            'email' => 'example@example.com',
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up caching feature for scanned app modules.
    |
    */
    'cache' => [
        'enabled' => env('APP_ENV', 'production') === 'production',
    ],
];
