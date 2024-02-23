<?php

use Laraneat\Modules\Enums\ModuleComponentType;

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
        | Composer File Template
        |--------------------------------------------------------------------------
        |
        | Here is the config for composer.json file, generated by this package
        |
        */
        'composer' => [
            'vendor' => 'app',
            'author' => [
                'name' => 'Example',
                'email' => 'example@example.com',
            ],
        ],

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
            ModuleComponentType::Action->value => [
                'path' => 'Actions',
                'generate' => true
            ],
            ModuleComponentType::ApiController->value => [
                'path' => 'UI/API/Controllers',
                'generate' => false
            ],
            ModuleComponentType::ApiQueryWizard->value => [
                'path' => 'UI/API/QueryWizards',
                'generate' => true
            ],
            ModuleComponentType::ApiRequest->value => [
                'path' => 'UI/API/Requests',
                'generate' => true
            ],
            ModuleComponentType::ApiResource->value => [
                'path' => 'UI/API/Resources',
                'generate' => true
            ],
            ModuleComponentType::ApiRoute->value => [
                'path' => 'UI/API/Routes',
                'generate' => true
            ],
            ModuleComponentType::ApiTest->value => [
                'path' => 'UI/API/Tests',
                'generate' => true
            ],
            ModuleComponentType::CliCommand->value => [
                'path' => 'UI/CLI/Commands',
                'generate' => false
            ],
            ModuleComponentType::CliTest->value => [
                'path' => 'UI/CLI/Tests',
                'generate' => false
            ],
            ModuleComponentType::Dto->value => [
                'path' => 'DTO',
                'generate' => true
            ],
            ModuleComponentType::Event->value => [
                'path' => 'Events',
                'generate' => false
            ],
            ModuleComponentType::Exception->value => [
                'path' => 'Exceptions',
                'generate' => false
            ],
            ModuleComponentType::Factory->value => [
                'path' => 'Data/Factories',
                'generate' => true
            ],
            ModuleComponentType::FeatureTest->value => [
                'path' => 'Tests/Feature',
                'generate' => false
            ],
            ModuleComponentType::Job->value => [
                'path' => 'Jobs',
                'generate' => false
            ],
            ModuleComponentType::Lang->value => [
                'path' => 'lang',
                'generate' => false
            ],
            ModuleComponentType::Listener->value => [
                'path' => 'Listeners',
                'generate' => false
            ],
            ModuleComponentType::Mail->value => [
                'path' => 'Mails',
                'generate' => false
            ],
            ModuleComponentType::Middleware->value => [
                'path' => 'Middleware',
                'generate' => false
            ],
            ModuleComponentType::Migration->value => [
                'path' => 'Data/Migrations',
                'generate' => true
            ],
            ModuleComponentType::Model->value => [
                'path' => 'Models',
                'generate' => true
            ],
            ModuleComponentType::Notification->value => [
                'path' => 'Notifications',
                'generate' => false
            ],
            ModuleComponentType::Observer->value => [
                'path' => 'Observers',
                'generate' => false
            ],
            ModuleComponentType::Policy->value => [
                'path' => 'Policies',
                'generate' => true
            ],
            ModuleComponentType::Provider->value => [
                'path' => 'Providers',
                'generate' => true
            ],
            ModuleComponentType::Rule->value => [
                'path' => 'Rules',
                'generate' => false
            ],
            ModuleComponentType::Seeder->value => [
                'path' => 'Data/Seeders',
                'generate' => true
            ],
            ModuleComponentType::WebController->value => [
                'path' => 'UI/WEB/Controllers',
                'generate' => false
            ],
            ModuleComponentType::WebRequest->value => [
                'path' => 'UI/WEB/Requests',
                'generate' => false,
            ],
            ModuleComponentType::WebRoute->value => [
                'path' => 'UI/WEB/Routes',
                'generate' => false
            ],
            ModuleComponentType::WebTest->value => [
                'path' => 'UI/WEB/Tests',
                'generate' => false
            ],
            ModuleComponentType::View->value => [
                'path' => 'resources/views',
                'generate' => false
            ],
            ModuleComponentType::UnitTest->value => [
                'path' => 'Tests/Unit',
                'generate' => false
            ],
        ],
    ],
];
