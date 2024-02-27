<?php

use Laraneat\Modules\Enums\ModuleComponentType;

return [
    /*
    |--------------------------------------------------------------------------
    | Modules path
    |--------------------------------------------------------------------------
    |
    | This path used for scan and save the generated module.
    |
    */
    'path' => base_path('modules'),

    /*
    |--------------------------------------------------------------------------
    | Module namespace prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for the namespace of the generated modules
    |
    */
    'namespace' => 'Modules',

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
    | Configuration composer.json of generated modules
    |
    */
    'composer' => [
        'vendor' => 'app',
        'author' => [
            'name' => 'Example',
            'email' => 'example@example.com'
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
    |
    */
    'components' => [
        ModuleComponentType::Action->value => [
            'path' => 'src/Actions',
            'namespace' => 'Actions'
        ],
        ModuleComponentType::ApiController->value => [
            'path' => 'src/UI/API/Controllers',
            'namespace' => 'UI\\API\\Controllers'
        ],
        ModuleComponentType::ApiQueryWizard->value => [
            'path' => 'src/UI/API/QueryWizards',
            'namespace' => 'UI\\API\\QueryWizards'
        ],
        ModuleComponentType::ApiRequest->value => [
            'path' => 'src/UI/API/Requests',
            'namespace' => 'UI\\API\\Requests'
        ],
        ModuleComponentType::ApiResource->value => [
            'path' => 'src/UI/API/Resources',
            'namespace' => 'UI\\API\\Resources'
        ],
        ModuleComponentType::ApiRoute->value => [
            'path' => 'src/UI/API/routes',
            'namespace' => 'UI\\API\\Routes'
        ],
        ModuleComponentType::ApiTest->value => [
            'path' => 'tests/UI/API',
            'namespace' => 'Tests\\UI\\API'
        ],
        ModuleComponentType::CliCommand->value => [
            'path' => 'src/UI/CLI/Commands',
            'namespace' => 'UI\\CLI\\Commands'
        ],
        ModuleComponentType::CliTest->value => [
            'path' => 'tests/UI/CLI',
            'namespace' => 'Tests\\UI\\CLI'
        ],
        ModuleComponentType::Dto->value => [
            'path' => 'src/DTO',
            'namespace' => 'DTO'
        ],
        ModuleComponentType::Event->value => [
            'path' => 'src/Events',
            'namespace' => 'Events'
        ],
        ModuleComponentType::Exception->value => [
            'path' => 'src/Exceptions',
            'namespace' => 'Exceptions'
        ],
        ModuleComponentType::Factory->value => [
            'path' => 'src/Factories',
            'namespace' => 'Exceptions'
        ],
        ModuleComponentType::FeatureTest->value => [
            'path' => 'tests/Feature',
            'namespace' => 'Tests\\Feature'
        ],
        ModuleComponentType::Job->value => [
            'path' => 'src/Jobs',
            'namespace' => 'Jobs'
        ],
        ModuleComponentType::Lang->value => [
            'path' => 'lang'
        ],
        ModuleComponentType::Listener->value => [
            'path' => 'src/Listeners',
            'namespace' => 'Listeners'
        ],
        ModuleComponentType::Mail->value => [
            'path' => 'src/Mails',
            'namespace' => 'Mails'
        ],
        ModuleComponentType::Middleware->value => [
            'path' => 'src/Middleware',
            'namespace' => 'Middleware'
        ],
        ModuleComponentType::Migration->value => [
            'path' => 'database/migrations'
        ],
        ModuleComponentType::Model->value => [
            'path' => 'src/Models',
            'namespace' => 'Models'
        ],
        ModuleComponentType::Notification->value => [
            'path' => 'src/Notifications',
            'namespace' => 'Notifications'
        ],
        ModuleComponentType::Observer->value => [
            'path' => 'src/Observers',
            'namespace' => 'Observers'
        ],
        ModuleComponentType::Policy->value => [
            'path' => 'src/Policies',
            'namespace' => 'Policies'
        ],
        ModuleComponentType::Provider->value => [
            'path' => 'src/Providers',
            'namespace' => 'Providers'
        ],
        ModuleComponentType::Rule->value => [
            'path' => 'src/Rules',
            'namespace' => 'Rules'
        ],
        ModuleComponentType::Seeder->value => [
            'path' => 'database/seeders',
            'namespace' => 'Database\\Seeders'
        ],
        ModuleComponentType::UnitTest->value => [
            'path' => 'tests/Unit',
            'namespace' => 'Tests\\Unit'
        ],
        ModuleComponentType::View->value => [
            'path' => 'resources/views'
        ],
        ModuleComponentType::WebController->value => [
            'path' => 'src/UI/WEB/Controllers',
            'namespace' => 'UI\\WEB\\Controllers'
        ],
        ModuleComponentType::WebRequest->value => [
            'path' => 'src/UI/WEB/Requests',
            'namespace' => 'UI\\WEB\\Requests'
        ],
        ModuleComponentType::WebRoute->value => [
            'path' => 'src/UI/WEB/routes',
            'namespace' => 'UI\\WEB\\Routes'
        ],
        ModuleComponentType::WebTest->value => [
            'path' => 'tests/UI/WEB',
            'namespace' => 'Tests\\UI\\WEB'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up caching feature for scanned modules.
    |
    */
    'cache' => [
        'enabled' => env('APP_ENV', 'production') === 'production',
    ],
];
