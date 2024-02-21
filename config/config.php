<?php

use Laraneat\Modules\Enums\ModuleComponentTypeEnum;

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
            ModuleComponentTypeEnum::Action->value => [
                'path' => 'Actions',
                'generate' => true
            ],
            ModuleComponentTypeEnum::ApiController->value => [
                'path' => 'UI/API/Controllers',
                'generate' => false
            ],
            ModuleComponentTypeEnum::ApiQueryWizard->value => [
                'path' => 'UI/API/QueryWizards',
                'generate' => true
            ],
            ModuleComponentTypeEnum::ApiRequest->value => [
                'path' => 'UI/API/Requests',
                'generate' => true
            ],
            ModuleComponentTypeEnum::ApiResource->value => [
                'path' => 'UI/API/Resources',
                'generate' => true
            ],
            ModuleComponentTypeEnum::ApiRoute->value => [
                'path' => 'UI/API/Routes',
                'generate' => true
            ],
            ModuleComponentTypeEnum::ApiTest->value => [
                'path' => 'UI/API/Tests',
                'generate' => true
            ],
            ModuleComponentTypeEnum::CliCommand->value => [
                'path' => 'UI/CLI/Commands',
                'generate' => false
            ],
            ModuleComponentTypeEnum::CliTest->value => [
                'path' => 'UI/CLI/Tests',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Dto->value => [
                'path' => 'DTO',
                'generate' => true
            ],
            ModuleComponentTypeEnum::Event->value => [
                'path' => 'Events',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Exception->value => [
                'path' => 'Exceptions',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Factory->value => [
                'path' => 'Data/Factories',
                'generate' => true
            ],
            ModuleComponentTypeEnum::FeatureTest->value => [
                'path' => 'Tests/Feature',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Job->value => [
                'path' => 'Jobs',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Lang->value => [
                'path' => 'lang',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Listener->value => [
                'path' => 'Listeners',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Mail->value => [
                'path' => 'Mails',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Middleware->value => [
                'path' => 'Middleware',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Migration->value => [
                'path' => 'Data/Migrations',
                'generate' => true
            ],
            ModuleComponentTypeEnum::Model->value => [
                'path' => 'Models',
                'generate' => true
            ],
            ModuleComponentTypeEnum::Notification->value => [
                'path' => 'Notifications',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Observer->value => [
                'path' => 'Observers',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Policy->value => [
                'path' => 'Policies',
                'generate' => true
            ],
            ModuleComponentTypeEnum::Provider->value => [
                'path' => 'Providers',
                'generate' => true
            ],
            ModuleComponentTypeEnum::Rule->value => [
                'path' => 'Rules',
                'generate' => false
            ],
            ModuleComponentTypeEnum::Seeder->value => [
                'path' => 'Data/Seeders',
                'generate' => true
            ],
            ModuleComponentTypeEnum::WebController->value => [
                'path' => 'UI/WEB/Controllers',
                'generate' => false
            ],
            ModuleComponentTypeEnum::WebRequest->value => [
                'path' => 'UI/WEB/Requests',
                'generate' => false,
            ],
            ModuleComponentTypeEnum::WebRoute->value => [
                'path' => 'UI/WEB/Routes',
                'generate' => false
            ],
            ModuleComponentTypeEnum::WebTest->value => [
                'path' => 'UI/WEB/Tests',
                'generate' => false
            ],
            ModuleComponentTypeEnum::View->value => [
                'path' => 'resources/views',
                'generate' => false
            ],
            ModuleComponentTypeEnum::UnitTest->value => [
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
